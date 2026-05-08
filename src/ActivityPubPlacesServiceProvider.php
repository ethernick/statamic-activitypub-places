<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces;

use Statamic\Providers\AddonServiceProvider;
use Ethernick\ActivityPubCore\Services\ActivityPubTypes;
use Ethernick\ActivityPubPlaces\Http\Controllers\PlaceController;
use Ethernick\ActivityPubPlaces\Http\Handlers\PlaceStoreHandler;
use Ethernick\ActivityPubPlaces\Http\Handlers\PlaceOutboxHandler;
use Ethernick\ActivityPubPlaces\Jobs\PlaceInboxHandler;
use Illuminate\Support\Arr;

class ActivityPubPlacesServiceProvider extends AddonServiceProvider
{
    protected $slug = 'activitypub-places';

    protected $routes = [
        'cp' => __DIR__ . '/routes/cp.php',
    ];

    protected $commands = [
        \Ethernick\ActivityPubPlaces\Console\Commands\ActivityPubPlacesInstall::class,
    ];

    protected $fieldtypes = [
        \Ethernick\ActivityPubPlaces\Fieldtypes\Location::class,
    ];

    protected $tags = [
        'activitypub_place' => \Ethernick\ActivityPubPlaces\Tags\ActivitypubPlace::class,
    ];

    public function boot(): void
    {
        parent::boot();


        // Register ActivityPub Type
        if (class_exists(ActivityPubTypes::class)) {
            ActivityPubTypes::register(
                'Place',
                'Place',
                null,
                PlaceController::class,
                ['places'],
                PlaceStoreHandler::class,
                PlaceOutboxHandler::class,
                PlaceInboxHandler::class
            );

            ActivityPubTypes::registerTransformHook(function ($entry, &$data) {
                if ($entry->collection()->handle() !== 'places') {
                    return;
                }

                $data['actions']['edit'] = 'activitypub:inbox:edit-place';
                $data['title'] = $entry->get('title');
                $data['osm_name'] = $entry->get('osm_name');
                $data['address'] = $entry->get('address');
                $data['latitude'] = $entry->get('latitude');
                $data['longitude'] = $entry->get('longitude');
                $data['osm_type'] = $entry->get('osm_type');
                $data['osm_id'] = $entry->get('osm_id');
            });

            // Inject location data into notes from the ActivityPub JSON payload
            ActivityPubTypes::registerTransformHook(function ($entry, &$data) {
                if ($entry->collection()->handle() !== 'notes') {
                    return;
                }

                $apJson  = $entry->get('activitypub_json');
                $payload = is_string($apJson) ? json_decode($apJson, true) : [];

                $lat  = $payload['location']['latitude'] ?? null;
                $lon  = $payload['location']['longitude'] ?? null;
                $name = $payload['location']['name'] ?? null;

                if ($lat && $lon) {
                    $data['location'] = [
                        'latitude'  => (float) $lat,
                        'longitude' => (float) $lon,
                        'name'      => $name,
                    ];
                }
            });

            // Transform Arrive activities to look like notes with maps
            ActivityPubTypes::registerTransformHook(function ($entry, &$data) {
                if ($entry->collection()->handle() !== 'activities') {
                    return;
                }

                $apJson = $entry->get('activitypub_json');
                $payload = is_string($apJson) ? json_decode($apJson, true) : [];

                if (($payload['type'] ?? '') !== 'Arrive') {
                    return;
                }

                // Leave as activity type so InboxActivity.vue handles specialized presentation
                
                // Use summary as content (Arrive summary is the check-in note)
                if (isset($payload['summary'])) {
                    $data['content'] = $payload['summary'];
                }

                // Extract location from 'location' or nested 'object'
                $loc = $payload['location'] ?? $payload['object'] ?? [];
                
                if (is_array($loc)) {
                    $lat  = $loc['latitude'] ?? null;
                    $lon  = $loc['longitude'] ?? null;
                    $name = $loc['name'] ?? null;

                    if ($lat && $lon) {
                        $data['location'] = [
                            'latitude'  => (float) $lat,
                            'longitude' => (float) $lon,
                            'name'      => $name,
                            'osm_type'  => $loc['osm_type'] ?? null,
                            'osm_id'    => $loc['osm_id'] ?? null,
                        ];
                    }
                }
            });

            ActivityPubTypes::registerOutboxHook(function ($entry, &$data) {
                if (!$entry->has('place')) {
                    return;
                }

                // If this is an Arrive activity, we don't need the redundant outer 'location'
                // because the 'object' is already the Place.
                if ($entry->collection()->handle() === 'activities' && $entry->get('type') === 'Arrive') {
                    return;
                }

                $placeId = $entry->get('place');
                if (is_array($placeId)) {
                    $placeId = $placeId[0] ?? null;
                }

                if (!$placeId) {
                    return;
                }

                $place = \Statamic\Facades\Entry::find($placeId);
                if (!$place || $place->collection()->handle() !== 'places') {
                    return;
                }

                // Add location to the ActivityPub JSON
                $data['location'] = [
                    'type' => 'Place',
                    'name' => $place->get('title'),
                    'latitude' => (float) $place->get('latitude'),
                    'longitude' => (float) $place->get('longitude'),
                ];
            });

            ActivityPubTypes::registerStoreHook(function ($entry) {
                if ($entry->collection()->handle() !== 'places') {
                    return;
                }

                $location = $entry->get('location') ?? [];
                $osmType = $entry->get('osm_type') ?? Arr::get($location, 'osm_type');
                $osmId = $entry->get('osm_id') ?? Arr::get($location, 'osm_id');

                if ($osmType && $osmId) {
                    $entry->slug("{$osmType}/{$osmId}");
                }

                if (!$entry->get('latitude') && Arr::get($location, 'latitude')) {
                    $entry->set('latitude', (float) Arr::get($location, 'latitude'));
                }
                if (!$entry->get('longitude') && Arr::get($location, 'longitude')) {
                    $entry->set('longitude', (float) Arr::get($location, 'longitude'));
                }
                if (!$entry->get('osm_name') && Arr::get($location, 'title')) {
                    $entry->set('osm_name', Arr::get($location, 'title'));
                }
            });
        }

        $this->registerNav();
        $this->registerSettings();
        $this->registerAssets();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'activitypub-places');
    }

    protected function registerSettings(): void
    {
        if (class_exists(\Ethernick\ActivityPubCore\Services\ActivityPubSettings::class)) {
            \Ethernick\ActivityPubCore\Services\ActivityPubSettings::registerTab(
                'places',
                'Places',
                'settings-places-tab',
                [
                    'nominatim_user_agent' => '',
                    'nominatim_url' => '',
                    'compatibility_mode' => false,
                ]
            );
        }
    }

    protected function registerNav(): void
    {
        $icon = @file_get_contents(__DIR__ . '/../resources/svg/places.svg') ?: 'pin';

        if (class_exists(\Ethernick\ActivityPubCore\Services\ActivityPubNav::class)) {
            \Ethernick\ActivityPubCore\Services\ActivityPubNav::register('places', [
                'label' => 'Places',
                'route' => 'activitypub.places.index',
                'icon' => $icon,
                'order' => 15,
                'section' => 'ActivityPub',
            ]);
        }
    }

    protected function registerAssets(): void
    {
        $packageName = 'ethernick/activitypub-places';
        $version = config('statamic.system.version') ?: (class_exists(\Statamic\Statamic::class) ? \Statamic\Statamic::version() : '6.13.0');
        $isV6 = version_compare($version, '6.0.0', '>=');
        $distSubdir = $isV6 ? 'v6' : 'v5';

        $distDir = __DIR__ . "/../dist/{$distSubdir}";
        if (is_dir($distDir)) {
            $this->publishes([
                "$distDir/js/cp.js" => public_path("vendor/$packageName/js/cp.js"),
                "$distDir/css/cp.css" => public_path("vendor/$packageName/css/cp.css"),
            ], 'activitypub-places');
        }

        \Statamic\Statamic::script($packageName, 'cp.js');
        \Statamic\Statamic::style($packageName, 'cp.css');
    }
}

<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;
use Ethernick\ActivityPubPlaces\Services\ActivityPubPlacesUtils;

class CheckInController extends CpController
{
    /**
     * Store a new check-in.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'actor'   => 'required|string',
            'content' => 'required|string',
            'place'   => 'required|array',
            'tags'    => 'nullable|array',
        ]);

        $placeData  = $data['place'];
        $placeEntry = null;

        // 1. Resolve or Create Place
        if ($placeData['is_internal'] ?? false) {
            $placeEntry = Entry::find($placeData['internal_id']);
        } else {
            $placeEntry = $this->createPlaceFromCheckIn($placeData, $data['actor']);
        }

        if (!$placeEntry) {
            return response()->json(['error' => 'Place not found or could not be created'], 422);
        }

        // 2. Create Arrive Activity
        $arrive = Entry::make()
            ->collection('activities')
            ->published(true)
            ->data([
                'type'        => 'Arrive',
                'actor'       => [$data['actor']],
                'object'      => $placeEntry->id(),
                'summary'     => $data['content'],
                'date'        => now()->format('Y-m-d H:i'),
                'is_internal' => true,
                'place'       => $placeEntry->id(),
            ]);
        $arrive->save();

        // 3. Compatibility Mode — create a plain Note for platforms that don't support Arrive
        $settingsPath  = ActivityPubUtils::settingsPath();
        $settings      = File::exists($settingsPath) ? YAML::parse(File::get($settingsPath)) : [];
        $compatibility = $settings['places']['compatibility_mode'] ?? $settings['compatibility_mode'] ?? false;

        if ($compatibility) {
            $this->createCompatibleNote($data, $placeEntry);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Check-in posted successfully',
            'activity_id' => $arrive->id(),
        ]);
    }

    /**
     * Create a new Place entry from OSM data.
     */
    protected function createPlaceFromCheckIn(array $data, string $actorId)
    {
        $slug = "{$data['osm_type']}/{$data['osm_id']}";

        $entry = Entry::make()
            ->collection('places')
            ->slug($slug);

        $name = $data['name'] ?? $data['display_name'];
        if (str_contains($name, ',')) {
            $name = explode(',', $name)[0];
        }

        $entry->set('title', $name);
        $entry->set('osm_name', $data['name'] ?? $data['display_name']);
        $entry->set('address', $data['address'] ?? $data['display_name'] ?? '');
        $entry->set('osm_type', (string) $data['osm_type']);
        $entry->set('osm_id', (string) $data['osm_id']);
        $entry->set('latitude', (float) $data['lat']);
        $entry->set('longitude', (float) $data['lon']);
        $entry->set('is_internal', true);
        $entry->set('actor', [$actorId]);
        $entry->set('location', [
            'osm_type'  => $data['osm_type'],
            'osm_id'    => $data['osm_id'],
            'latitude'  => (float) $data['lat'],
            'longitude' => (float) $data['lon'],
            'address'   => $data['address'] ?? $data['display_name'] ?? '',
            'title'     => $name,
        ]);

        $entry->save();
        return $entry;
    }

    /**
     * Create a standard Note activity for platforms that don't support Arrive.
     * Includes an OpenStreetMap link so followers can see the location interactively.
     */
    protected function createCompatibleNote(array $data, $placeEntry): void
    {
        $placeName = $placeEntry->get('title');
        $osmUrl    = ActivityPubPlacesUtils::osmUrl($placeEntry->data()->all());
        $footer    = "\n\n📍 **[{$placeName}]({$osmUrl})**";

        $note = Entry::make()
            ->collection('notes')
            ->published(true)
            ->data([
                'content'     => $data['content'] . $footer,
                'actor'       => [$data['actor']],
                'date'        => now()->format('Y-m-d H:i'),
                'is_internal' => true,
                'place'       => $placeEntry->id(),
                'tags'        => $data['tags'] ?? [],
            ]);

        $note->save();
    }
}

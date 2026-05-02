<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Collection;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;

class ActivityPubPlacesInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitypub:places:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure ActivityPub Places.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Installing ActivityPub Places...');

        $this->ensurePlacesCollection();
        $this->configureActivityPubSettings();
        $this->publishAssets();

        $this->info('ActivityPub Places installation complete!');
    }

    protected function ensurePlacesCollection(): void
    {
        $handle = 'places';
        $title = 'Places';

        if (Collection::findByHandle($handle)) {
            $this->comment("✓ Collection [{$handle}] already exists.");
        } else {
            $this->info("Creating collection: {$title} ({$handle})...");
            Collection::make($handle)
                ->title($title)
                ->save();
        }

        $this->ensureBlueprint($handle);
    }

    protected function ensureBlueprint(string $handle): void
    {
        $blueprintDir = resource_path("blueprints/collections/{$handle}");
        if (!File::exists($blueprintDir)) {
            File::makeDirectory($blueprintDir, 0755, true);
        }

        $blueprintPath = "{$blueprintDir}/place.yaml";
        $stubPath = __DIR__ . '/../../../resources/blueprints/places/place.yaml';

        if (!File::exists($stubPath)) {
            $this->error("Blueprint template for Places not found at [{$stubPath}]");
            return;
        }

        $targetFields = YAML::parse(File::get($stubPath));

        if (!File::exists($blueprintPath)) {
            $this->info("Creating default blueprint for Places at [{$blueprintPath}]...");
            File::put($blueprintPath, YAML::dump($targetFields));
        } else {
            $this->info("Blueprint already exists at [{$blueprintPath}]. Skipping override.");
        }
    }

    protected function configureActivityPubSettings(): void
    {
        $settingsPath = ActivityPubUtils::settingsPath();
        $settingsDir = dirname($settingsPath);

        if (!File::exists($settingsDir)) {
            File::makeDirectory($settingsDir, 0755, true);
        }

        if (!File::exists($settingsPath)) {
            $this->info("Creating ActivityPub settings at [{$settingsPath}]...");
            File::put($settingsPath, YAML::dump([]));
        }

        $settings = YAML::parse(File::get($settingsPath));
        
        if (isset($settings['places'])) {
            $this->comment("✓ Places already configured in activitypub.yaml.");
        } else {
            $this->info("Enabling Places in activitypub.yaml...");
            $settings['places'] = [
                'enabled' => true,
                'federated' => true,
                'type' => 'Place',
            ];
            File::put($settingsPath, YAML::dump($settings));
        }

        // Update places.yaml content collection settings to ensure date: true and route
        $collectionPath = ActivityPubUtils::collectionPath('places');
        if (File::exists($collectionPath)) {
            $this->info("Updating Places collection configuration...");
            $config = YAML::parse(File::get($collectionPath));
            $config['date'] = true;
            // Use requested nested routing: /places/{osm_type}/{osm_id}
            $config['route'] = '/places/{osm_type}/{osm_id}';
            $config['blueprints'] = ['place'];
            File::put($collectionPath, YAML::dump($config));
        }
    }

    protected function publishAssets(): void
    {
        $this->info('Publishing ActivityPub Places assets...');
        $this->call('vendor:publish', [
            '--provider' => 'Ethernick\ActivityPubPlaces\ActivityPubPlacesServiceProvider',
            '--tag' => 'activitypub-places',
            '--force' => true,
        ]);
    }
}

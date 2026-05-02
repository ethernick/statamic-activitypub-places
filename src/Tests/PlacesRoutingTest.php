<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Statamic\Facades\Entry;

class PlacesRoutingTest extends PlacesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the places collection exists in the sandbox
        $this->setupCollections(['places']);
        
        // Seed the blueprint to the sandbox
        $stubPath = base_path('addons/ethernick/ActivityPubPlaces/resources/blueprints/places/place.yaml');
        $dest = $this->sandboxPath . '/resources/blueprints/collections/places/place.yaml';
        
        if (!\Illuminate\Support\Facades\File::exists(dirname($dest))) {
            \Illuminate\Support\Facades\File::makeDirectory(dirname($dest), 0755, true);
        }
        \Illuminate\Support\Facades\File::copy($stubPath, $dest);
    }

    public function test_entry_generates_osm_based_url()
    {
        $entry = Entry::make()
            ->collection('places')
            ->slug('test-place')
            ->data([
                'title' => 'Test Coffee Shop',
                'osm_type' => 'node',
                'osm_id' => '1234567',
            ]);
            
        $entry->save();

        // Assert the URL matches our custom pattern: /places/{osm_type}/{osm_id}
        $this->assertEquals('/places/node/1234567', $entry->url());
    }

    public function test_route_resolves_correctly()
    {
        $entry = Entry::make()
            ->collection('places')
            ->slug('test-place-2')
            ->data([
                'title' => 'Test Park',
                'osm_type' => 'way',
                'osm_id' => '9876543',
            ]);
            
        $entry->save();

        // In Statamic, to test the front-end resolution, we have to hit the URL
        $this->get('/places/way/9876543')
             ->assertStatus(200);
    }
}

<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Illuminate\Support\Facades\Http;
use Statamic\Facades\Entry;

class PlaceSearchControllerTest extends PlacesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupCollections(['places']);
        
        // Seed the blueprint to the sandbox
        $stubPath = base_path('addons/ethernick/ActivityPubPlaces/resources/blueprints/places/place.yaml');
        $dest = $this->sandboxPath . '/resources/blueprints/collections/places/place.yaml';
        
        if (!\Illuminate\Support\Facades\File::exists(dirname($dest))) {
            \Illuminate\Support\Facades\File::makeDirectory(dirname($dest), 0755, true);
        }
        \Illuminate\Support\Facades\File::copy($stubPath, $dest);
        
        // Setup User-Agent
        \Statamic\Facades\File::put(\Ethernick\ActivityPubCore\Services\ActivityPubUtils::settingsPath(), \Statamic\Facades\YAML::dump([
            'nominatim_user_agent' => 'TestAgent/1.0',
        ]));
    }

    public function test_search_endpoint_returns_json()
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'results' => [['display_name' => 'Paris']]
            ], 200)
        ]);

        $this->actingAsSuperUser()
             ->get('/cp/activitypub/places/search?q=Paris')
             ->assertStatus(200)
             ->assertJsonStructure(['results']);
    }

    public function test_store_endpoint_creates_entry()
    {
        $payload = [
            'osm_type' => 'node',
            'osm_id' => '123456',
            'title' => 'Test Cafe',
            'address' => '123 Test St',
            'latitude' => 48.85,
            'longitude' => 2.35,
        ];

        $this->actingAsSuperUser()
             ->post('/cp/activitypub/places/search', $payload)
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $entry = Entry::query()->where('collection', 'places')->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Test Cafe', $entry->get('title'));
        $this->assertEquals('node', $entry->get('osm_type'));
        $this->assertEquals('123456', $entry->get('osm_id'));
    }

    public function test_store_fails_with_invalid_data()
    {
        $this->actingAsSuperUser()
             ->json('POST', '/cp/activitypub/places/search', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['osm_type', 'osm_id', 'latitude', 'longitude']);
             
        $this->assertEquals(0, Entry::query()->where('collection', 'places')->count());
    }

    public function test_returns_empty_results_for_empty_query()
    {
        $this->actingAsSuperUser()
             ->get('/cp/activitypub/places/search?q=')
             ->assertStatus(200)
             ->assertJson(['results' => []]);
    }

    public function test_reverse_endpoint_returns_discovery_list()
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'place_id' => '98765',
                'osm_type' => 'way',
                'osm_id' => '136048710',
                'display_name' => 'Paris',
                'lat' => '48.85',
                'lon' => '2.35',
            ], 200),
            'overpass-api.de/api/interpreter*' => Http::response([
                'elements' => [
                    [
                        'type' => 'way',
                        'id' => 892861849,
                        'center' => ['lat' => 48.85, 'lon' => 2.35],
                        'tags' => [
                            'description' => 'Indoor batting cages',
                            'sport' => 'baseball'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->actingAsSuperUser()
             ->get('/cp/activitypub/places/reverse?lat=48.85&lon=2.35')
             ->assertStatus(200)
             ->assertJsonStructure(['results'])
             ->assertJsonPath('results.0.name', 'Point: 48.85, 2.35')
             ->assertJsonPath('results.1.name', 'Indoor batting cages');
    }
}

<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Statamic\Facades\Entry;

class PlacesDashboardTest extends PlacesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupCollections(['places', 'activities', 'actors']);
        
        // Seed the blueprint to the sandbox
        $stubPath = base_path('addons/ethernick/ActivityPubPlaces/resources/blueprints/places/place.yaml');
        $dest = $this->sandboxPath . '/resources/blueprints/collections/places/place.yaml';
        
        if (!\Illuminate\Support\Facades\File::exists(dirname($dest))) {
            \Illuminate\Support\Facades\File::makeDirectory(dirname($dest), 0755, true);
        }
        \Illuminate\Support\Facades\File::copy($stubPath, $dest);
    }

    public function test_all_places_endpoint_returns_saved_places()
    {
        Entry::make()
            ->collection('places')
            ->slug('way/123456')
            ->data([
                'title' => 'Test Place',
                'osm_name' => 'Test Cafe',
                'address' => '123 Test St, Test City, Test Country',
                'latitude' => 49.16,
                'longitude' => -122.58,
                'is_internal' => true,
            ])
            ->save();

        $this->actingAsSuperUser()
            ->get('/cp/activitypub/places/all')
            ->assertStatus(200)
            ->assertJsonCount(1, 'places')
            ->assertJsonPath('places.0.title', 'Test Place')
            ->assertJsonPath('places.0.is_internal', true);
    }

    public function test_place_activities_endpoint_returns_filtered_stream()
    {
        $place = Entry::make()
            ->collection('places')
            ->slug('way/123456')
            ->data([
                'title' => 'Test Place',
                'activitypub_id' => 'https://example.com/places/1'
            ]);
        $place->save();

        // Linked activity
        Entry::make()
            ->collection('activities')
            ->slug('activity-1')
            ->data([
                'title' => 'Check-in at Test Place',
                'object' => [
                    'type' => 'Place',
                    'id' => 'https://example.com/places/1'
                ],
                'date' => now()->toDateTimeString()
            ])
            ->save();

        // Unrelated activity
        Entry::make()
            ->collection('activities')
            ->slug('activity-2')
            ->data([
                'title' => 'Other activity',
                'object' => ['id' => 'https://example.com/other'],
                'date' => now()->toDateTimeString()
            ])
            ->save();

        $this->actingAsSuperUser()
            ->get("/cp/activitypub/places/{$place->id()}/activities")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Check-in at Test Place');
    }

    public function test_place_activities_unauthorized_fails()
    {
        $this->get('/cp/activitypub/places/all')
            ->assertRedirect();
    }
}

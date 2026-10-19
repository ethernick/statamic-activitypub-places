<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Illuminate\Support\Facades\Http;
use Ethernick\ActivityPubPlaces\Services\NominatimService;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;

class NominatimServiceTest extends PlacesTestCase
{
    protected NominatimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure settings exist with a User-Agent
        $path = ActivityPubUtils::settingsPath();
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }
        File::put($path, YAML::dump([
            'nominatim_user_agent' => 'TestAgent/1.0',
        ]));

        $this->service = new NominatimService();
    }

    public function test_search_sends_correct_headers()
    {
        Http::fake();

        $this->service->search('Paris');

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent', 'TestAgent/1.0') &&
                   $request->url() == 'https://nominatim.openstreetmap.org/search?q=Paris&format=jsonv2&addressdetails=1&polygon_geojson=1&limit=10';
        });
    }

    public function test_search_returns_results_from_api()
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['display_name' => 'Paris, France', 'lat' => '48.85', 'lon' => '2.35']
            ], 200)
        ]);

        $response = $this->service->search('Paris');

        $this->assertArrayHasKey('results', $response);
        $this->assertCount(1, $response['results']);
        $this->assertEquals('Paris, France', $response['results'][0]['display_name']);
    }

    public function test_search_uses_dynamic_user_agent_when_not_configured()
    {
        // Clear custom User-Agent from settings
        File::put(ActivityPubUtils::settingsPath(), YAML::dump([]));
        $service = new NominatimService();

        Http::fake();
        $service->search('Paris');

        Http::assertSent(function ($request) {
            return str_contains($request->header('User-Agent')[0], 'ethernick/statamic-activitypub-places/')
                && str_contains($request->header('User-Agent')[0], 'Statamic/');
        });
    }

    public function test_details_fetches_correct_osm_id()
    {
        Http::fake([
            'nominatim.openstreetmap.org/lookup*' => Http::response([
                ['place_id' => 123, 'display_name' => 'Eiffel Tower']
            ], 200)
        ]);

        $response = $this->service->details('node', '12345');

        $this->assertEquals('Eiffel Tower', $response['display_name']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'osm_ids=N12345');
        });
    }
}

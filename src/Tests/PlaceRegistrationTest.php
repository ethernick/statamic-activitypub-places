<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Ethernick\ActivityPubCore\Services\ActivityPubTypes;
use Ethernick\ActivityPubCore\Services\ActivityPubSettings;
use Statamic\Facades\User;

class PlaceRegistrationTest extends PlacesTestCase
{
    public function test_place_type_is_registered()
    {
        $types = app(ActivityPubTypes::class)->all();
        
        $this->assertArrayHasKey('Place', $types);
        $this->assertEquals('Place', $types['Place']['label']);
        $this->assertEquals('Ethernick\ActivityPubPlaces\Http\Controllers\PlaceController', $types['Place']['controller']);
    }

    public function test_settings_tab_is_registered()
    {
        $tabs = ActivityPubSettings::getTabs();
        
        $found = false;
        foreach ($tabs as $tab) {
            if ($tab['id'] === 'places') {
                $found = true;
                $this->assertEquals('Places', $tab['label']);
                $this->assertEquals('settings-places-tab', $tab['component']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Places settings tab not found in ActivityPubSettings');
    }

    public function test_cp_route_is_accessible()
    {
        // Must be logged in as a Super Admin to access CP routes
        $user = User::make()->makeSuper()->id('test-admin')->email('admin@example.com')->save();
        
        $this->actingAs($user)
             ->get(cp_route('activitypub.places.index'))
             ->assertStatus(200)
             ->assertSee('Places');
    }

    public function test_place_blueprint_exists()
    {
        $blueprintPath = base_path('addons/ethernick/ActivityPubPlaces/resources/blueprints/places/place.yaml');
        $this->assertFileExists($blueprintPath);
        
        $yaml = \Statamic\Facades\YAML::file($blueprintPath)->parse();
        $this->assertEquals('Place', $yaml['title']);
    }
}

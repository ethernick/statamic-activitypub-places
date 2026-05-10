<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure places collection specifically exists (base TestCase might not have it)
        $this->setupCollections(['places']);
        
        $this->actor = Entry::make()
            ->collection('actors')
            ->slug('test-actor')
            ->data(['title' => 'Test Actor', 'is_internal' => true]);
        $this->actor->save();

        $this->user = User::make()
            ->email('test@example.com')
            ->data(['actors' => [$this->actor->id()]]);
        $this->user->makeSuper();
        $this->user->save();
    }

    public function test_can_check_in_to_existing_place()
    {
        $place = Entry::make()
            ->collection('places')
            ->slug('existing-place')
            ->data([
                'title' => 'Existing Place',
                'latitude' => 45.0,
                'longitude' => -75.0,
            ]);
        $place->save();

        $response = $this->actingAs($this->user)
            ->post(cp_route('activitypub.places.checkin.store'), [
                'actor' => $this->actor->id(),
                'content' => 'Checking in here!',
                'place' => [
                    'is_internal' => true,
                    'internal_id' => $place->id(),
                    'name' => 'Existing Place'
                ]
            ]);

        if ($response->status() !== 200) {
            dump($response->getContent());
        }

        $response->assertStatus(200);
        
        $this->assertDatabaseHasEntry('activities', [
            'type' => 'Arrive',
            'actor' => $this->actor->id(),
            'object' => $place->id(),
            'summary' => 'Checking in here!'
        ]);
    }

    public function test_can_check_in_and_create_new_place()
    {
        $response = $this->actingAs($this->user)
            ->post(cp_route('activitypub.places.checkin.store'), [
                'actor' => $this->actor->id(),
                'content' => 'Checking in to a new place!',
                'place' => [
                    'is_internal' => false,
                    'osm_type' => 'node',
                    'osm_id' => '123456',
                    'name' => 'New OSM Place',
                    'lat' => '45.123',
                    'lon' => '-75.456',
                    'display_name' => 'New OSM Place, City, Country'
                ]
            ]);

        if ($response->status() !== 200) {
            dump($response->getContent());
        }

        $response->assertStatus(200);

        // Verify place created
        $place = Entry::query()->where('collection', 'places')->where('osm_id', '123456')->first();
        $this->assertNotNull($place);
        $this->assertEquals('New OSM Place', $place->get('title'));

        // Verify activity created
        $this->assertDatabaseHasEntry('activities', [
            'type' => 'Arrive',
            'actor' => $this->actor->id(),
            'object' => $place->id()
        ]);
    }

    public function test_compatibility_mode_creates_note()
    {
        // Enable compatibility mode in sandbox settings
        $path = ActivityPubUtils::settingsPath();
        $settings = File::exists($path) ? YAML::parse(File::get($path)) : [];
        $settings['places']['compatibility_mode'] = true;
        File::put($path, YAML::dump($settings));

        $place = Entry::make()
            ->collection('places')
            ->slug('comp-place')
            ->data(['title' => 'Comp Place', 'latitude' => 0, 'longitude' => 0]);
        $place->save();

        $response = $this->actingAs($this->user)
            ->post(cp_route('activitypub.places.checkin.store'), [
                'actor' => $this->actor->id(),
                'content' => 'Dual posting!',
                'place' => [
                    'is_internal' => true,
                    'internal_id' => $place->id(),
                    'name' => 'Comp Place'
                ]
            ]);

        if ($response->status() !== 200) {
            dump($response->getContent());
        }

        $response->assertStatus(200);

        // Verify Note created
        $note = Entry::query()->where('collection', 'notes')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('Dual posting!', $note->get('content'));
        $this->assertStringContainsString('Comp Place', $note->get('content'));
        $this->assertStringNotContainsString('View on Map', $note->get('content')); // Redundant link removed
        $this->assertEquals($place->id(), $note->get('place'));
    }

    protected function assertDatabaseHasEntry($collection, $data)
    {
        $query = Entry::query()->where('collection', $collection);
        $entries = $query->get();
        
        $found = $entries->filter(function($entry) use ($data) {
            foreach ($data as $key => $value) {
                $actual = $entry->get($key);
                
                // Handle array-wrapped values (like actors)
                if (is_array($actual) && !is_array($value)) {
                    if (!in_array($value, $actual)) return false;
                } elseif ($actual != $value) {
                    return false;
                }
            }
            return true;
        });

        if ($found->count() === 0) {
            dump("Entries in {$collection}:", $entries->map->data()->all());
            dump("Searching for:", $data);
        }

        $this->assertTrue($found->count() > 0, "Entry not found in collection {$collection}");
    }
}

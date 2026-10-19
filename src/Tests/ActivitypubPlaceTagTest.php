<?php

namespace Ethernick\ActivityPubPlaces\Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\Tag;
use Tests\TestCase;
use Mockery;
use Ethernick\ActivityPubPlaces\Tags\ActivitypubPlace;

class ActivitypubPlaceTagTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_place_map_tag_renders_with_mocked_entry()
    {
        // 1. Mock the Entry
        $mockEntry = Mockery::mock(\Statamic\Entries\Entry::class);
        $mockEntry->shouldReceive('id')->andReturn('mock-place-123');
        $mockEntry->shouldReceive('get')->with('latitude')->andReturn(45.123);
        $mockEntry->shouldReceive('get')->with('longitude')->andReturn(-75.456);
        $mockEntry->shouldReceive('get')->with('title')->andReturn('Mock Cafe');
        
        $mockCollection = Mockery::mock();
        $mockCollection->shouldReceive('handle')->andReturn('places');
        $mockEntry->shouldReceive('collection')->andReturn($mockCollection);

        // 2. Mock the Facade
        Entry::shouldReceive('find')->with('mock-place-123')->andReturn($mockEntry);

        // 3. Instantiate Tag directly
        $tag = new ActivitypubPlace();
        $tag->setContext([]);
        $tag->setParameters(['id' => 'mock-place-123']);
        $tag->method = 'map';

        $output = $tag->map();

        // 4. Assertions
        $this->assertStringContainsString('45.123', $output);
        $this->assertStringContainsString('-75.456', $output);
        $this->assertStringContainsString('Mock Cafe', $output);
        $this->assertStringContainsString('leaflet.js', $output);
    }

    public function test_place_links_tag_renders_with_mocked_entry()
    {
        // 1. Mock the Entry
        $mockEntry = Mockery::mock(\Statamic\Entries\Entry::class);
        $mockEntry->shouldReceive('id')->andReturn('mock-place-456');
        $mockEntry->shouldReceive('get')->with('latitude')->andReturn(45.123);
        $mockEntry->shouldReceive('get')->with('longitude')->andReturn(-75.456);
        $mockEntry->shouldReceive('get')->with('osm_type')->andReturn('way');
        $mockEntry->shouldReceive('get')->with('osm_id')->andReturn('999');
        $mockEntry->shouldReceive('get')->with('title')->andReturn('Mock Park');
        
        $mockCollection = Mockery::mock();
        $mockCollection->shouldReceive('handle')->andReturn('places');
        $mockEntry->shouldReceive('collection')->andReturn($mockCollection);

        // 2. Mock the Facade
        Entry::shouldReceive('find')->with('mock-place-456')->andReturn($mockEntry);

        // 3. Instantiate Tag directly
        $tag = new ActivitypubPlace();
        $tag->setContext([]);
        $tag->setParameters(['id' => 'mock-place-456']);
        $tag->method = 'links';

        $output = $tag->links();

        // 4. Assertions
        $this->assertIsArray($output);
        $this->assertEquals('https://www.openstreetmap.org/way/999', $output[0]['url']);
        $this->assertStringContainsString('google.com/maps', $output[1]['url']);
    }

    public function test_tag_resolves_place_from_non_place_context_entry_with_location()
    {
        // 1. Mock Place Entry
        $mockPlace = Mockery::mock(\Statamic\Entries\Entry::class);
        $mockPlace->shouldReceive('id')->andReturn('mock-place-789');
        $mockPlace->shouldReceive('get')->with('latitude')->andReturn(49.145);
        $mockPlace->shouldReceive('get')->with('longitude')->andReturn(-122.666);
        $mockPlace->shouldReceive('get')->with('title')->andReturn('Event Arena');

        $mockPlaceCollection = Mockery::mock();
        $mockPlaceCollection->shouldReceive('handle')->andReturn('places');
        $mockPlace->shouldReceive('collection')->andReturn($mockPlaceCollection);

        // 2. Mock Event Entry that points to Place via 'location'
        $mockEvent = Mockery::mock(\Statamic\Entries\Entry::class);
        $mockEvent->shouldReceive('id')->andReturn('mock-event-999');
        $mockEvent->shouldReceive('get')->with('location')->andReturn('mock-place-789');
        $mockEvent->shouldReceive('get')->with('place')->andReturn(null);

        $mockEventCollection = Mockery::mock();
        $mockEventCollection->shouldReceive('handle')->andReturn('events');
        $mockEvent->shouldReceive('collection')->andReturn($mockEventCollection);

        Entry::shouldReceive('find')->with('mock-event-999')->andReturn($mockEvent);
        Entry::shouldReceive('find')->with('mock-place-789')->andReturn($mockPlace);

        // 3. Instantiate Tag with event context (simulating viewing an event page)
        $tag = new ActivitypubPlace();
        $tag->setContext(['id' => 'mock-event-999', 'location' => 'mock-place-789']);
        $tag->setParameters([]);
        $tag->method = 'map';

        $output = $tag->map();

        $this->assertStringContainsString('49.145', $output);
        $this->assertStringContainsString('-122.666', $output);
        $this->assertStringContainsString('Event Arena', $output);
    }

    public function test_place_links_tag_pair_returns_links_data_structure()
    {
        $mockPlace = Mockery::mock(\Statamic\Entries\Entry::class);
        $mockPlace->shouldReceive('id')->andReturn('mock-place-abc');
        $mockPlace->shouldReceive('get')->with('latitude')->andReturn(49.145);
        $mockPlace->shouldReceive('get')->with('longitude')->andReturn(-122.666);
        $mockPlace->shouldReceive('get')->with('osm_type')->andReturn('way');
        $mockPlace->shouldReceive('get')->with('osm_id')->andReturn('12345');
        $mockPlace->shouldReceive('get')->with('title')->andReturn('Event Arena');

        $mockPlaceCollection = Mockery::mock();
        $mockPlaceCollection->shouldReceive('handle')->andReturn('places');
        $mockPlace->shouldReceive('collection')->andReturn($mockPlaceCollection);

        Entry::shouldReceive('find')->with('mock-place-abc')->andReturn($mockPlace);

        $tag = new ActivitypubPlace();
        $tag->setContext(['id' => 'mock-place-abc']);
        $tag->setParameters([]);
        $tag->isPair = true;

        $output = $tag->links();

        $this->assertIsArray($output);
        $this->assertEquals('https://www.openstreetmap.org/way/12345', $output[0]['url']);
        $this->assertStringContainsString('google.com/maps', $output[1]['url']);
        $this->assertStringContainsString('maps.apple.com', $output[2]['url']);
    }
}

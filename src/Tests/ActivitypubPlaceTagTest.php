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
}

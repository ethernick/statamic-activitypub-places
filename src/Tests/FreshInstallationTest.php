<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Tests\TestCase;
use Ethernick\ActivityPubCore\Tests\Concerns\ProvidesSandbox;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;
use Statamic\Facades\Collection;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use PHPUnit\Framework\Attributes\Test;

class FreshInstallationTest extends TestCase
{
    use ProvidesSandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupSandbox();
    }

    protected function tearDown(): void
    {
        $this->teardownSandbox();
        parent::tearDown();
    }

    #[Test]
    public function it_executes_places_install_command_in_isolation(): void
    {
        $settingsPath = ActivityPubUtils::settingsPath();
        if (File::exists($settingsPath)) {
            File::delete($settingsPath);
        }

        $blueprintPath = ActivityPubUtils::blueprintPath('collections/places/place.yaml');
        if (File::exists($blueprintPath)) {
            File::delete($blueprintPath);
        }

        $this->artisan('activitypub:places:install')
            ->expectsOutput('Installing ActivityPub Places...')
            ->expectsOutput('ActivityPub Places installation complete!')
            ->assertExitCode(0);

        // Verify places collection exists
        $collection = Collection::findByHandle('places');
        $this->assertNotNull($collection);
        $this->assertEquals('Places', $collection->title());

        // Verify blueprint template was installed
        $this->assertFileExists($blueprintPath);
        $blueprintData = YAML::file($blueprintPath)->parse();
        $this->assertEquals('Place', $blueprintData['title'] ?? null);

        // Verify activitypub.yaml has places configured
        $this->assertFileExists($settingsPath);
        $settings = YAML::file($settingsPath)->parse();
        $this->assertArrayHasKey('places', $settings);
        $this->assertTrue($settings['places']['enabled'] ?? false);
        $this->assertTrue($settings['places']['federated'] ?? false);
        $this->assertEquals('Place', $settings['places']['type'] ?? null);
    }
}

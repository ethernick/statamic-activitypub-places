<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tests;

use Tests\TestCase;
use Ethernick\ActivityPubCore\Tests\Concerns\ProvidesSandbox;

abstract class PlacesTestCase extends TestCase
{
    use ProvidesSandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupSandbox();
        
        // Ensure Places collection exists for tests
        $this->setupCollections(['places']);

        // Ensure stache is clear
        \Statamic\Facades\Stache::clear();
    }

    protected function actingAsSuperUser()
    {
        $user = \Statamic\Facades\User::make()
            ->email('test@example.com')
            ->makeSuper()
            ->save();

        return $this->actingAs($user);
    }
}

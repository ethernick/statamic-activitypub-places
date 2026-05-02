<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Handlers;

use Ethernick\ActivityPubCore\Contracts\StoreHandlerInterface;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;

class PlaceStoreHandler implements StoreHandlerInterface
{
    public function store(Request $request): EntryContract
    {
        // Stub for now
        throw new \Exception('Place storage not yet implemented');
    }
}

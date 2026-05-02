<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Controllers;

use Statamic\Http\Controllers\CP\CpController;

class PlaceCpController extends CpController
{
    public function index()
    {
        return view('activitypub-places::index', [
            'title' => 'Places',
        ]);
    }
}

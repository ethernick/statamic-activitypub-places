<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Handlers;

use Ethernick\ActivityPubCore\Contracts\OutboxHandlerInterface;
use Statamic\Entries\Entry;

class PlaceOutboxHandler implements OutboxHandlerInterface
{
    /**
     * Enrich the ActivityPub data array with Place-specific fields.
     */
    public function format(array $data, Entry $entry): array
    {
        // Decouple Names: 
        // 1. Use osm_name as the public identity name
        // 2. Use the internal title as the summary (as suggested by the user)
        $osmName = $entry->get('osm_name');
        if ($osmName) {
            $data['name'] = $osmName;
        }

        $data['summary'] = $entry->get('title');

        // Add geographic coordinates with fallbacks
        $data['latitude'] = (float) ($entry->get('latitude') ?? $entry->get('location.latitude'));
        $data['longitude'] = (float) ($entry->get('longitude') ?? $entry->get('location.longitude'));

        // Map OSM metadata if available
        if ($entry->get('osm_type') && $entry->get('osm_id')) {
            $data['osm_type'] = $entry->get('osm_type');
            $data['osm_id'] = $entry->get('osm_id');
        }

        return $data;
    }
}

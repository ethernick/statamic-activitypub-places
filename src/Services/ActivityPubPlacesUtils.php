<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Services;

class ActivityPubPlacesUtils
{
    /**
     * Generate a rich OpenStreetMap URL from location data.
     * Prioritizes node/way/relation links, falls back to coordinate markers.
     *
     * @param array|object $data Array or object containing lat/lon and osm_type/id
     * @return string
     */
    public static function osmUrl($data): string
    {
        $data = is_object($data) ? (array) $data : $data;

        $lat = $data['latitude'] ?? $data['lat'] ?? null;
        $lon = $data['longitude'] ?? $data['lon'] ?? null;
        $osmType = $data['osm_type'] ?? null;
        $osmId = $data['osm_id'] ?? null;

        if ($osmType && $osmId) {
            return "https://www.openstreetmap.org/{$osmType}/{$osmId}#map=19/{$lat}/{$lon}";
        }

        if ($lat && $lon) {
            return "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lon}#map=17/{$lat}/{$lon}";
        }

        return "https://www.openstreetmap.org";
    }
}

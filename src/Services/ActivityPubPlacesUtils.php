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

    /**
     * Create or resolve a Place entry from Nominatim location data.
     *
     * @param array $data OSM data
     * @param string $actorId ID of actor creating the place
     * @return \Statamic\Entries\Entry|null
     */
    public static function createPlace(array $data, string $actorId)
    {
        $slug = "{$data['osm_type']}/{$data['osm_id']}";

        // Check if it already exists
        $existing = \Statamic\Facades\Entry::query()
            ->where('collection', 'places')
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            return $existing;
        }

        $entry = \Statamic\Facades\Entry::make()
            ->collection('places')
            ->slug($slug);

        $name = $data['name'] ?? $data['display_name'];
        if (str_contains($name, ',')) {
            $name = explode(',', $name)[0];
        }

        $entry->set('title', $name);
        $entry->set('osm_name', $data['name'] ?? $data['display_name']);
        $entry->set('address', $data['address'] ?? $data['display_name'] ?? '');
        $entry->set('osm_type', (string) $data['osm_type']);
        $entry->set('osm_id', (string) $data['osm_id']);
        $entry->set('latitude', (float) $data['lat']);
        $entry->set('longitude', (float) $data['lon']);
        $entry->set('is_internal', true);
        $entry->set('actor', [$actorId]);
        $entry->set('location', [
            'osm_type'  => $data['osm_type'],
            'osm_id'    => $data['osm_id'],
            'latitude'  => (float) $data['lat'],
            'longitude' => (float) $data['lon'],
            'address'   => $data['address'] ?? $data['display_name'] ?? '',
            'title'     => $name,
        ]);

        $entry->save();
        return $entry;
    }
}

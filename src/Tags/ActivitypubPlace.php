<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Tags;

use Statamic\Tags\Tags;
use Statamic\Facades\Entry;

class ActivitypubPlace extends Tags
{
    public function index()
    {
        $place = $this->getPlace();

        if (!$place) {
            return null;
        }

        return [
            'id' => $place->id(),
            'title' => $place->get('title'),
            'osm_name' => $place->get('osm_name'),
            'address' => $place->get('address'),
            'latitude' => $place->get('latitude'),
            'longitude' => $place->get('longitude'),
            'osm_type' => $place->get('osm_type'),
            'osm_id' => $place->get('osm_id'),
            'content' => $place->get('content'),
            'place_map' => $this->generateMap($place),
            'place_links' => $this->generateLinks($place),
        ];
    }

    /**
     * The {{ place:map }} tag
     */
    public function map()
    {
        return $this->generateMap();
    }

    /**
     * The {{ place:links }} tag
     */
    public function links()
    {
        return $this->generateLinks();
    }

    /**
     * Internal map generator
     */
    protected function generateMap($place = null)
    {
        $place = $place ?? $this->getPlace();
        if (!$place) return '';

        $lat = $place->get('latitude');
        $lon = $place->get('longitude');
        $title = addslashes($place->get('title') ?? 'Location');

        // Unique ID for the map container
        $mapId = 'map-' . uniqid();

        $html = "
            <div id='{$mapId}' style='height: 400px; width: 100%; border-radius: 8px; margin-bottom: 1rem;'></div>
            <link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' />
            <script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var map = L.map('{$mapId}').setView([{$lat}, {$lon}], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    L.marker([{$lat}, {$lon}]).addTo(map)
                        .bindPopup('{$title}')
                        .openPopup();
                });
            </script>
        ";

        return $html;
    }

    /**
     * Internal links generator
     */
    protected function generateLinks($place = null)
    {
        $place = $place ?? $this->getPlace();
        if (!$place) return [];

        $lat = $place->get('latitude');
        $lon = $place->get('longitude');
        $osmType = $place->get('osm_type');
        $osmId = $place->get('osm_id');

        $osmUrl = ($osmType && $osmId)
            ? "https://www.openstreetmap.org/{$osmType}/{$osmId}"
            : "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lon}#map=16/{$lat}/{$lon}";

        $name = urlencode($place->get('title') ?? '');
        $query = $name ? "{$name}+{$lat},{$lon}" : "{$lat},{$lon}";

        return [
            ['type' => 'osm', 'url' => $osmUrl],
            ['type' => 'google', 'url' => "https://www.google.com/maps/search/?api=1&query={$query}"],
            ['type' => 'apple', 'url' => "https://maps.apple.com/?q={$query}"],
        ];
    }

    protected function getPlace()
    {
        $id = $this->params->get('id');

        if (!$id && $this->context->get('id')) {
            $id = $this->context->get('id');
        }

        if (!$id && $this->context->get('entry')) {
            $entry = $this->context->get('entry');
            $id = is_object($entry) ? $entry->id() : $entry;
        }

        if (!$id) {
            $osmType = $this->context->get('osm_type');
            $osmId = $this->context->get('osm_id');
            if ($osmType && $osmId) {
                $place = Entry::query()
                    ->where('collection', 'places')
                    ->where('osm_type', (string) $osmType)
                    ->where('osm_id', (string) $osmId)
                    ->first();
                if ($place) return $place;
            }
        }

        if (!$id) {
            return null;
        }

        $place = Entry::find((string) $id);

        if ($place && $place->collection()->handle() === 'places') {
            return $place;
        }

        return null;
    }
}

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

        $data = [
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

        if ($this->isPair) {
            return $this->parse($data);
        }

        return $data;
    }

    /**
     * The {{ activitypub_place:map }} tag
     */
    public function map()
    {
        return $this->generateMap();
    }

    /**
     * The {{ activitypub_place:links }} tag
     */
    public function links()
    {
        $links = $this->generateLinks();

        if ($this->isPair) {
            return $this->parseLoop($links);
        }

        return $links;
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
        if (!$lat || !$lon) return '';

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
        if (!$lat || !$lon) return [];

        $osmType = $place->get('osm_type');
        $osmId = $place->get('osm_id');

        $osmUrl = ($osmType && $osmId)
            ? "https://www.openstreetmap.org/{$osmType}/{$osmId}"
            : "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lon}#map=16/{$lat}/{$lon}";

        $name = urlencode($place->get('title') ?? '');
        $googleUrl = "https://www.google.com/maps/search/?api=1&query={$lat},{$lon}";
        $appleUrl = $name
            ? "https://maps.apple.com/?ll={$lat},{$lon}&q={$name}"
            : "https://maps.apple.com/?q={$lat},{$lon}";

        return [
            ['type' => 'osm', 'url' => $osmUrl],
            ['type' => 'google', 'url' => $googleUrl],
            ['type' => 'apple', 'url' => $appleUrl],
        ];
    }

    protected function getPlace(): ?\Statamic\Contracts\Entries\Entry
    {
        // 1. Explicit tag parameter (id, place, location)
        $param = $this->params->get('id') 
            ?? $this->params->get('place') 
            ?? $this->params->get('location');

        if ($param !== null) {
            $place = $this->resolvePlaceEntry($param);
            if ($place) {
                return $place;
            }
        }

        // 2. Context 'location' or 'place' variable (e.g. from an Event or Note entry in context)
        $contextLocation = $this->context->get('location') ?? $this->context->get('place');
        if ($contextLocation !== null) {
            $place = $this->resolvePlaceEntry($contextLocation);
            if ($place) {
                return $place;
            }
        }

        // 3. Current context entry object
        $contextEntry = $this->context->get('entry');
        if ($contextEntry !== null) {
            $place = $this->resolvePlaceEntry($contextEntry);
            if ($place) {
                return $place;
            }
        }

        // 4. Current context 'id'
        $contextId = $this->context->get('id');
        if ($contextId !== null) {
            $place = $this->resolvePlaceEntry($contextId);
            if ($place) {
                return $place;
            }
        }

        // 5. OSM coordinates/identifiers in context (e.g. for dynamic OSM routing)
        $osmType = $this->context->get('osm_type');
        $osmId = $this->context->get('osm_id');
        if ($osmType && $osmId) {
            $place = Entry::query()
                ->where('collection', 'places')
                ->where('osm_type', (string) $osmType)
                ->where('osm_id', (string) $osmId)
                ->first();
            if ($place) {
                return $place;
            }
        }

        return null;
    }

    protected function resolvePlaceEntry(mixed $value): ?\Statamic\Contracts\Entries\Entry
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Unwrap Statamic Value object (from augmented array or context)
        if ($value instanceof \Statamic\Fields\Value) {
            $value = $value->value();
        }

        // Unwrap Collections or Arrays (e.g. entries field type)
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->first();
        } elseif (is_array($value)) {
            $value = reset($value);
        }

        // Unwrap Value object if collection elements were Values
        if ($value instanceof \Statamic\Fields\Value) {
            $value = $value->value();
        }

        // If it's an Entry instance
        if ($value instanceof \Statamic\Contracts\Entries\Entry) {
            if ($value->collection() && $value->collection()->handle() === 'places') {
                return $value;
            }

            // If it's another collection entry (e.g. Event), look for its location field
            $nestedLocation = $value->get('location') ?? $value->get('place');
            if ($nestedLocation && $nestedLocation !== $value) {
                return $this->resolvePlaceEntry($nestedLocation);
            }

            return null;
        }

        // If it's a string ID or slug
        if (is_string($value) && !empty($value)) {
            $entry = Entry::find($value);
            if ($entry) {
                if ($entry->collection() && $entry->collection()->handle() === 'places') {
                    return $entry;
                }

                // If found entry is an event or other type with a location, resolve that location
                $nestedLocation = $entry->get('location') ?? $entry->get('place');
                if ($nestedLocation && $nestedLocation !== $value) {
                    return $this->resolvePlaceEntry($nestedLocation);
                }
            }
        }

        return null;
    }
}

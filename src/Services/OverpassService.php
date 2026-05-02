<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class OverpassService
{
    /**
     * Fetch nearby OSM features within a radius of the given coordinates.
     */
    public function fetchNearby(float $lat, float $lon, int $radius = 50): Collection
    {
        $query = <<<OVERPASS
[out:json];
(
  node(around:{$radius}, {$lat}, {$lon});
  way(around:{$radius}, {$lat}, {$lon});
  relation(around:{$radius}, {$lat}, {$lon});
);
out tags center;
OVERPASS;

        $response = Http::asForm()
            ->withUserAgent('StatamicActivityPub/1.0')
            ->timeout(5)
            ->post('https://overpass-api.de/api/interpreter', [
                'data' => $query,
            ]);

        if ($response->failed()) {
            return collect([]);
        }

        $data = $response->json();
        $elements = collect($data['elements'] ?? []);

        return $elements->map(function ($element) {
            $tags = $element['tags'] ?? [];
            
            // Extract the best possible name/description
            // Higher priority on name, then description (for the Batting Cages case), then building type
            $name = $tags['name'] ?? $tags['description'] ?? $tags['official_name'] ?? $tags['building:name'] ?? $tags['building'] ?? $tags['amenity'] ?? $tags['highway'] ?? 'Unknown Feature';
            
            // Clean up name if it's just a generic building/highway tag
            if ($name === 'yes' || $name === 'building') {
                $name = $tags['description'] ?? $tags['amenity'] ?? $tags['building'] ?? 'Unnamed Building';
            }

            // Normalize coordinates (Overpass returns 'center' for ways/relations)
            $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
            $lon = $element['lon'] ?? ($element['center']['lon'] ?? null);

            return [
                'place_id' => $element['type'] . '/' . $element['id'],
                'osm_type' => $element['type'],
                'osm_id' => (string) $element['id'],
                'name' => $name,
                'display_name' => $name,
                'category' => $tags['amenity'] ?? $tags['building'] ?? $tags['highway'] ?? $element['type'],
                'type' => $element['type'],
                'lat' => (string) $lat,
                'lon' => (string) $lon,
                'tags' => $tags,
            ];
        })->filter(function ($item) {
            if ($item['lat'] === null) return false;
            
            // Filter out "noise" results (Unnamed items without high-value tags)
            $tags = $item['tags'] ?? [];
            $highValueTags = [
                'amenity', 'leisure', 'shop', 'tourism', 'historic', 
                'sport', 'office', 'craft', 'emergency', 'place'
            ];

            $hasHighValueTag = false;
            foreach ($highValueTags as $tag) {
                if (isset($tags[$tag])) {
                    $hasHighValueTag = true;
                    break;
                }
            }

            // Keep it if it has a specific name, description, or a high-value category
            return isset($tags['name']) || 
                   isset($tags['description']) || 
                   isset($tags['building:name']) ||
                   $hasHighValueTag;
        });
    }
}

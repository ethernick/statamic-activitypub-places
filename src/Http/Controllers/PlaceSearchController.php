<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Ethernick\ActivityPubPlaces\Services\NominatimService;
use Ethernick\ActivityPubCore\Storage\ActivityPubPayload;

class PlaceSearchController extends CpController
{
    protected NominatimService $nominatim;
    protected \Ethernick\ActivityPubPlaces\Services\OverpassService $overpass;

    public function __construct(
        NominatimService $nominatim,
        \Ethernick\ActivityPubPlaces\Services\OverpassService $overpass
    ) {
        $this->nominatim = $nominatim;
        $this->overpass = $overpass;
    }



    /**
     * Search both internal places and OSM (Alias for find).
     */
    public function search(Request $request)
    {
        return $this->find($request);
    }

    /**
     * Search both internal places and OSM.
     */
    public function find(Request $request)
    {
        $query = (string) $request->get('q');
        
        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        // 1. Search Internal Places
        $internalResults = \Statamic\Facades\Entry::query()
            ->where('collection', 'places')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('osm_name', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%");
            })
            ->get();

        $internal = $internalResults->map(function ($entry) {
            return [
                'place_id' => 'internal/' . $entry->id(),
                'osm_type' => $entry->get('osm_type'),
                'osm_id' => $entry->get('osm_id'),
                'name' => $entry->get('title'),
                'display_name' => $entry->get('address') ? "{$entry->get('title')}, {$entry->get('address')}" : $entry->get('title'),
                'lat' => (string) $entry->get('latitude'),
                'lon' => (string) $entry->get('longitude'),
                'is_internal' => true,
                'internal_id' => $entry->id(),
                'address' => $entry->get('address'),
            ];
        });

        // 2. Search Nominatim (with tight timeout if we already have internal results)
        $external = collect();
        try {
            // If we have an exact title match internally, we might want to be even faster on the external call
            $hasExactInternal = $internal->contains(fn($i) => strtolower($i['name']) === strtolower($query));
            $timeout = $hasExactInternal ? 3 : 5;

            $nomResults = $this->nominatim->search($query, ['timeout' => $timeout]);
            $external = collect($nomResults['results'] ?? []);
            
            if (isset($nomResults['error'])) {
                \Illuminate\Support\Facades\Log::warning("ActivityPubPlaces: Nominatim search error: " . $nomResults['error']);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ActivityPubPlaces: Nominatim search failed/timed out: " . $e->getMessage());
        }

        // Filter out external results that are already internal
        $internalOsmIds = $internalResults->map(fn($e) => (string)$e->get('osm_type') . '/' . (string)$e->get('osm_id'))->filter()->toArray();
        
        $external = $external->filter(function ($res) use ($internalOsmIds) {
            $id = ($res['osm_type'] ?? '') . '/' . ($res['osm_id'] ?? '');
            return !in_array($id, $internalOsmIds);
        });

        // Unified results
        $results = $internal->concat($external)->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Get details for an OSM object.
     */
    public function details(Request $request)
    {
        $type = $request->get('type');
        $id = $request->get('id');

        if (empty($type) || empty($id)) {
            return response()->json(['error' => 'Missing type or id'], 400);
        }

        $details = $this->nominatim->details($type, $id);

        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 404);
        }

        return response()->json($details);
    }

    /**
     * Reverse geocode coordinates.
     */
    public function reverse(Request $request)
    {
        $lat = (float) $request->get('lat');
        $lon = (float) $request->get('lon');

        if (empty($lat) || empty($lon)) {
            return response()->json(['error' => 'Missing lat or lon'], 400);
        }

        $results = [];

        // 1. Always create an "Exact Coordinate Point" result
        $latData = round($lat, 6);
        $lonData = round($lon, 6);
        $results[] = [
            'place_id' => "point/{$latData}_{$lonData}",
            'osm_type' => 'point',
            'osm_id' => "{$latData}_{$lonData}",
            'name' => "Point: {$latData}, {$lonData}",
            'display_name' => "{$latData}, {$lonData}",
            'category' => 'POINT',
            'type' => 'coordinate',
            'lat' => (string) $lat,
            'lon' => (string) $lon,
            'is_exact' => true,
        ];

        // 2. Search internal places by proximity (100m radius)
        $internalNearby = \Statamic\Facades\Entry::query()
            ->where('collection', 'places')
            ->get()
            ->filter(function ($entry) use ($lat, $lon) {
                $pLat = (float) $entry->get('latitude');
                $pLon = (float) $entry->get('longitude');
                if (!$pLat || !$pLon) return false;
                
                return $this->calculateDistance($lat, $lon, $pLat, $pLon) <= 0.1; // 100 meters
            })
            ->map(function ($entry) {
                return [
                    'place_id' => 'internal/' . $entry->id(),
                    'osm_type' => $entry->get('osm_type'),
                    'osm_id' => $entry->get('osm_id'),
                    'name' => $entry->get('title'),
                    'display_name' => $entry->get('address') ? "{$entry->get('title')}, {$entry->get('address')}" : $entry->get('title'),
                    'lat' => (string) $entry->get('latitude'),
                    'lon' => (string) $entry->get('longitude'),
                    'is_internal' => true,
                    'internal_id' => $entry->id(),
                    'address' => $entry->get('address'),
                    'is_exact' => true,
                ];
            });

        foreach ($internalNearby as $in) {
            $results[] = $in;
        }

        // 3. Query Overpass (with error handling and timeout)
        try {
            $overpassResults = $this->overpass->fetchNearby($lat, $lon, 50);
            foreach ($overpassResults as $op) {
                // Deduplicate against internal results by OSM ID
                $exists = collect($results)->contains(function ($r) use ($op) {
                    return ($r['osm_type'] ?? '') === $op['osm_type'] && ($r['osm_id'] ?? '') === $op['osm_id'];
                });

                if (!$exists) {
                    $results[] = $op;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ActivityPubPlaces: Overpass reverse failed: " . $e->getMessage());
        }

        // 4. Query Nominatim (with error handling and timeout)
        try {
            $nomResult = $this->nominatim->reverse($lat, $lon);
            if (!isset($nomResult['error'])) {
                // Update the display name of our exact point with address info if found
                if (isset($nomResult['display_name'])) {
                    $results[0]['display_name'] = "{$latData}, {$lonData} near " . $nomResult['display_name'];
                    $results[0]['address'] = $nomResult['address'] ?? null;
                }
                
                // Deduplicate Nominatim result
                $exists = collect($results)->contains(function ($r) use ($nomResult) {
                    return ($r['osm_type'] ?? '') === $nomResult['osm_type'] && ($r['osm_id'] ?? '') === $nomResult['osm_id'];
                });

                if (!$exists) {
                    $results[] = $nomResult;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ActivityPubPlaces: Nominatim reverse failed: " . $e->getMessage());
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Calculate distance between two points in km.
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radius of the earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get available actors for selection.
     */
    public function actors()
    {
        $actors = \Statamic\Facades\Entry::query()
            ->where('collection', 'actors')
            ->where('is_internal', true)
            ->get()
            ->map(function ($entry) {
                return \Ethernick\ActivityPubCore\Services\ActivityPubUtils::transformActorForCp($entry);
            })
            ->values();

        return response()->json(['actors' => $actors]);
    }

    /**
     * Create a new Place entry from OSM data.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'osm_type' => 'required|string',
            'osm_id' => 'required|string',
            'title' => 'required|string',
            'address' => 'nullable|string',
            'latitude' => 'required',
            'longitude' => 'required',
            'actor' => 'nullable|string',
            'content' => 'nullable|string',
        ]);

        // Use OSM Type and ID as the slug (path) to achieve /places/way/12345
        $slug = "{$data['osm_type']}/{$data['osm_id']}";

        $entry = \Statamic\Facades\Entry::make()
            ->collection('places')
            ->slug($slug);

        $entry->set('title', $data['title']);
        $entry->set('osm_name', $request->input('name') ?? $data['title']);
        $entry->set('address', $data['address'] ?? '');
        $entry->set('content', $data['content'] ?? '');
        $entry->set('osm_type', (string) $data['osm_type']);
        $entry->set('osm_id', (string) $data['osm_id']);
        $entry->set('latitude', (float) $data['latitude']);
        $entry->set('longitude', (float) $data['longitude']);
        $entry->set('is_internal', true);
        
        // Handle Actor
        $actorId = $data['actor'] ?? null;
        if (!$actorId) {
            $user = \Statamic\Facades\User::current();
            $actors = $user ? $user->get('actors', []) : [];
            $actorId = $actors[0] ?? null;
        }
        $entry->set('actor', $actorId);

        // Populate nested location field for the CP fieldtype map
        $entry->set('location', [
            'osm_type' => $data['osm_type'],
            'osm_id' => $data['osm_id'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'address' => $data['address'] ?? '',
            'title' => $data['title'],
            'name' => $request->input('name'),
        ]);

        $entry->save();

        return response()->json([
            'success' => true,
            'url' => $entry->editUrl(),
            'entry_id' => $entry->id()
        ]);
    }

    /**
     * Update an existing Place entry.
     * Note: Location data (OSM ID/Type) is locked on creation to preserve federation integrity.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
        ]);

        $entry = \Statamic\Facades\Entry::find($id);

        if (!$entry || $entry->collection()->handle() !== 'places') {
            return response()->json(['error' => 'Place not found'], 404);
        }

        $entry->set('title', $data['title']);
        $entry->set('content', $data['content'] ?? '');
        
        $entry->save();

        return response()->json([
            'success' => true,
            'url' => $entry->editUrl(),
            'entry_id' => $entry->id()
        ]);
    }

    /**
     * Get all places for map visualization.
     */
    public function all()
    {
        $places = \Statamic\Facades\Entry::query()
            ->where('collection', 'places')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id(),
                    'title' => $entry->get('title'),
                    'osm_name' => $entry->get('osm_name'),
                    'address' => $entry->get('address'),
                    'latitude' => (float) $entry->get('latitude'),
                    'longitude' => (float) $entry->get('longitude'),
                    'osm_type' => $entry->get('osm_type'),
                    'osm_id' => $entry->get('osm_id'),
                    'is_internal' => (bool) $entry->get('is_internal'),
                    'edit_url' => $entry->editUrl(),
                    'uri' => $entry->get('activitypub_id'),
                ];
            });

        return response()->json(['places' => $places]);
    }

    /**
     * Get related activities/notes/events for a specific place.
     */
    public function activities(string $id)
    {
        $place = \Statamic\Facades\Entry::find($id);

        if (!$place || $place->collection()->handle() !== 'places') {
            return response()->json(['error' => 'Place not found'], 404);
        }

        $uri = $place->get('activitypub_id');
        $placeId = $place->id();

        // 1. Query activities collection (check-ins, likes, boosts, arrives, etc.)
        $activities = \Statamic\Facades\Entry::query()
            ->where('collection', 'activities')
            ->get()
            ->filter(function ($entry) use ($uri, $placeId) {
                $obj = $entry->get('object');
                if (is_array($obj) && isset($obj['id']) && in_array($obj['id'], array_filter([$uri, $placeId]), true)) {
                    return true;
                }
                if ($entry->get('place') && in_array($entry->get('place'), array_filter([$placeId, $uri]), true)) {
                    return true;
                }
                if ($entry->get('location') && in_array($entry->get('location'), array_filter([$placeId, $uri]), true)) {
                    return true;
                }
                $raw = ActivityPubPayload::for($entry)->raw();
                if ($raw && (($placeId && str_contains($raw, (string)$placeId)) || ($uri && str_contains($raw, (string)$uri)))) {
                    return true;
                }
                return false;
            })
            ->values();

        // 2. Query notes collection (posts tagged at this venue/place)
        $notes = \Statamic\Facades\Entry::query()
            ->where('collection', 'notes')
            ->get()
            ->filter(function ($entry) use ($uri, $placeId) {
                $loc = $entry->get('location') ?? $entry->get('place');
                if (is_array($loc)) {
                    return in_array($placeId, $loc, true) || ($uri && in_array($uri, $loc, true));
                }
                return in_array($loc, array_filter([$placeId, $uri]), true);
            })
            ->values();

        // 3. Query events collection (events scheduled at this venue/place)
        $events = \Statamic\Facades\Entry::query()
            ->where('collection', 'events')
            ->get()
            ->filter(function ($entry) use ($uri, $placeId) {
                $loc = $entry->get('location') ?? $entry->get('place');
                if (is_array($loc)) {
                    return in_array($placeId, $loc, true) || ($uri && in_array($uri, $loc, true));
                }
                return in_array($loc, array_filter([$placeId, $uri]), true);
            })
            ->values();

        $allEntries = $activities->concat($notes)->concat($events);

        $allData = $allEntries->map(function ($entry) {
            return \Ethernick\ActivityPubCore\Services\ActivityPubUtils::transformEntryForFeed($entry);
        })
        ->sortByDesc(function ($item) {
            return $item['date'] ?? '';
        })
        ->values();

        return response()->json(['data' => $allData]);
    }
}

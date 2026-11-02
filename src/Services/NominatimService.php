<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Services;

use Illuminate\Support\Facades\Http;
use Statamic\Facades\Addon;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;

class NominatimService
{
    protected string $baseUrl = 'https://nominatim.openstreetmap.org';
    protected ?string $userAgent = null;

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $path = ActivityPubUtils::settingsPath();
        $customAgent = null;
        if (File::exists($path)) {
            $settings = YAML::parse(File::get($path));
            $customAgent = $settings['nominatim_user_agent'] ?? null;
            $this->baseUrl = $settings['nominatim_url'] ?? 'https://nominatim.openstreetmap.org';
        }

        $this->userAgent = $this->resolveUserAgent($customAgent);
    }

    /**
     * Resolve a dynamic, OSM-compliant User-Agent header:
     * AppName/Version (+SiteURL)
     */
    public function resolveUserAgent(?string $customAgent = null): string
    {
        if (!empty($customAgent)) {
            return trim($customAgent);
        }

        $version = Addon::get('ethernick/statamic-activitypub-places')?->version() ?? '0.1.0';
        $statamicVersion = class_exists(\Statamic\Statamic::class) ? \Statamic\Statamic::version() : (config('statamic.system.version') ?? '5.x');
        $siteUrl = config('app.url') ?? 'https://localhost';

        return "ethernick/statamic-activitypub-places/{$version} Statamic/{$statamicVersion} (+{$siteUrl})";
    }

    public function getUserAgent(): string
    {
        return $this->userAgent ?? $this->resolveUserAgent();
    }

    /**
     * Search for places by query string.
     */
    public function search(string $query, array $params = []): array
    {
        if (empty($this->userAgent)) {
            return [
                'error' => 'Nominatim User-Agent not configured. Please set it in ActivityPub Places settings.',
                'results' => []
            ];
        }

        $timeout = $params['timeout'] ?? 10;
        unset($params['timeout']);

        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept-Language' => app()->getLocale(),
        ])->timeout($timeout)
          ->get($this->baseUrl . '/search', array_merge([
            'q' => $query,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'polygon_geojson' => 1,
            'limit' => 10,
        ], $params));

        if ($response->failed()) {
            return [
                'error' => 'Search request failed: ' . $response->reason(),
                'results' => []
            ];
        }

        return [
            'results' => $response->json()
        ];
    }

    /**
     * Get details for a specific OSM object.
     */
    public function details(string $osmType, string $osmId): array
    {
        if (empty($this->userAgent)) {
            return ['error' => 'User-Agent required'];
        }

        // OSM Type maps: node -> N, way -> W, relation -> R
        $type = strtoupper(substr($osmType, 0, 1));
        
        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
        ])->timeout(5)
          ->get($this->baseUrl . '/lookup', [
            'osm_ids' => $type . $osmId,
            'format' => 'jsonv2',
            'polygon_geojson' => 1,
            'addressdetails' => 1,
        ]);

        if ($response->failed()) {
            return ['error' => 'Lookup failed'];
        }

        $results = $response->json();
        return count($results) > 0 ? $results[0] : ['error' => 'Not found'];
    }

    /**
     * Reverse geocode coordinates to an OSM object.
     */
    public function reverse(float $lat, float $lon): array
    {
        if (empty($this->userAgent)) {
            return ['error' => 'User-Agent required'];
        }

        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept-Language' => app()->getLocale(),
        ])->timeout(5)
          ->get($this->baseUrl . '/reverse', [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'polygon_geojson' => 1,
        ]);

        if ($response->failed()) {
            return ['error' => 'Reverse geocode failed: ' . $response->reason()];
        }

        $result = $response->json();
        return isset($result['place_id']) ? $result : ['error' => 'No location found at these coordinates'];
    }
}

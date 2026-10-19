<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Ethernick\ActivityPubCore\Services\ActivityPubUtils;
use Ethernick\ActivityPubPlaces\Services\ActivityPubPlacesUtils;

class CheckInController extends CpController
{
    /**
     * Store a new check-in.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'actor'   => 'required|string',
            'content' => 'required|string',
            'place'   => 'required|array',
            'tags'    => 'nullable|array',
        ]);

        $placeData  = $data['place'];
        $placeEntry = null;

        // 1. Resolve or Create Place
        if ($placeData['is_internal'] ?? false) {
            $placeEntry = Entry::find($placeData['internal_id']);
        } else {
            $placeEntry = ActivityPubPlacesUtils::createPlace($placeData, $data['actor']);
        }

        if (!$placeEntry) {
            return response()->json(['error' => 'Place not found or could not be created'], 422);
        }

        // 2. Create Arrive Activity
        $arriveUuid = (string) \Illuminate\Support\Str::uuid();
        $arriveNow = now();
        $arrive = Entry::make()
            ->collection('activities')
            ->id($arriveUuid)
            ->slug($arriveUuid)
            ->date($arriveNow)
            ->published(true)
            ->data([
                'type'        => 'Arrive',
                'actor'       => [$data['actor']],
                'object'      => $placeEntry->id(),
                'summary'     => $data['content'],
                'date'        => $arriveNow->format('Y-m-d H:i:s'),
                'is_internal' => true,
                'place'       => $placeEntry->id(),
            ]);
        $arrive->save();

        // 3. Compatibility Mode — create a plain Note for platforms that don't support Arrive
        $settingsPath  = ActivityPubUtils::settingsPath();
        $settings      = File::exists($settingsPath) ? YAML::parse(File::get($settingsPath)) : [];
        $compatibility = $settings['places']['compatibility_mode'] ?? $settings['compatibility_mode'] ?? false;

        if ($compatibility) {
            $this->createCompatibleNote($data, $placeEntry);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Check-in posted successfully',
            'activity_id' => $arrive->id(),
        ]);
    }

    /**
     * Create a standard Note activity for platforms that don't support Arrive.
     * Includes an OpenStreetMap link so followers can see the location interactively.
     */
    protected function createCompatibleNote(array $data, $placeEntry): void
    {
        $placeName = $placeEntry->get('title');
        $osmUrl    = ActivityPubPlacesUtils::osmUrl($placeEntry->data()->all());
        $footer    = "\n\n📍 **[{$placeName}]({$osmUrl})**";

        $noteUuid = (string) \Illuminate\Support\Str::uuid();
        $noteNow  = now();

        $note = Entry::make()
            ->collection('notes')
            ->id($noteUuid)
            ->slug($noteUuid)
            ->date($noteNow)
            ->published(true)
            ->data([
                'content'     => $data['content'] . $footer,
                'actor'       => [$data['actor']],
                'date'        => $noteNow->format('Y-m-d H:i:s'),
                'is_internal' => true,
                'place'       => $placeEntry->id(),
                'tags'        => $data['tags'] ?? [],
            ]);

        $note->save();
    }
}

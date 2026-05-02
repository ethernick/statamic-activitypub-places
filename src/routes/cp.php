<?php

use Illuminate\Support\Facades\Route;
use Ethernick\ActivityPubPlaces\Http\Controllers\PlaceCpController;
use Ethernick\ActivityPubPlaces\Http\Controllers\PlaceSearchController;

Route::namespace('Ethernick\ActivityPubPlaces\Http\Controllers')->group(function () {
    Route::get('activitypub/places', [PlaceCpController::class, 'index'])->name('activitypub.places.index');
    Route::group(['prefix' => 'activitypub/places', 'as' => 'activitypub.places.'], function () {
        Route::get('all', [PlaceSearchController::class, 'all'])->name('all');
        Route::get('{id}/activities', [PlaceSearchController::class, 'activities'])->name('activities');
        Route::get('actors', [PlaceSearchController::class, 'actors'])->name('actors');
        Route::post('search', [PlaceSearchController::class, 'store'])->name('store');
        Route::put('{id}', [PlaceSearchController::class, 'update'])->name('update');
        Route::get('search', [PlaceSearchController::class, 'search'])->name('search');
        Route::get('find', [PlaceSearchController::class, 'find'])->name('find');
        Route::get('details', [PlaceSearchController::class, 'details'])->name('details');
        Route::get('reverse', [PlaceSearchController::class, 'reverse'])->name('reverse');
        Route::post('checkin', [\Ethernick\ActivityPubPlaces\Http\Controllers\CheckInController::class, 'store'])->name('checkin.store');
    });
});

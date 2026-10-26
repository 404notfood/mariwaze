<?php

use App\Http\Controllers\Api\CruiseDurationController;
use App\Http\Controllers\Api\CspReportController;
use App\Http\Controllers\Api\MarineConditionsController;
use App\Http\Controllers\Api\PlaceSearchController;
use App\Http\Controllers\Api\RoutePreviewController;
use App\Http\Controllers\Api\SeamarkController;
use Illuminate\Support\Facades\Route;

Route::post('/security/csp-reports', CspReportController::class)->middleware('throttle:csp-reports');

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::middleware('throttle:marine')->get('/marine/conditions', MarineConditionsController::class);
    Route::middleware('throttle:route-preview')->get('/routes/preview', RoutePreviewController::class);
    Route::middleware('throttle:map-data')->get('/seamarks', SeamarkController::class);
    Route::middleware('throttle:map-data')->get('/places', PlaceSearchController::class);
    Route::middleware('throttle:marine')->get('/cruises/duration', CruiseDurationController::class);
});

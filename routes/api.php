<?php

use App\Http\Controllers\Api\CruiseDurationController;
use App\Http\Controllers\Api\MarineConditionsController;
use App\Http\Controllers\Api\RoutePreviewController;
use App\Http\Controllers\Api\SeamarkController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:marine')->get('/marine/conditions', MarineConditionsController::class);
Route::middleware('throttle:marine')->get('/routes/preview', RoutePreviewController::class);
Route::get('/seamarks', SeamarkController::class);
Route::get('/cruises/duration', CruiseDurationController::class);

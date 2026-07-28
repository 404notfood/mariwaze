<?php

use App\Http\Controllers\Api\MarineConditionsController;
use App\Http\Controllers\Api\CruiseDurationController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:marine')->get('/marine/conditions', MarineConditionsController::class);
Route::get('/cruises/duration', CruiseDurationController::class);

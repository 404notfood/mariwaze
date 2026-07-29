<?php

use App\Http\Controllers\Api\CruiseDurationController;
use App\Http\Controllers\Api\MarineConditionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:marine')->get('/marine/conditions', MarineConditionsController::class);
Route::get('/cruises/duration', CruiseDurationController::class);

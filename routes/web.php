<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnchorageController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\CruiseController;
use App\Http\Controllers\CruiseTravelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\SafetyController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('explorer', [ExploreController::class, 'index'])->name('explore');
    Route::get('carte', [MapController::class, 'index'])->name('map');
    Route::get('securite', [SafetyController::class, 'index'])->name('security');

    Route::get('ports/{slug}', [PortController::class, 'show'])->name('port');
    Route::get('mouillages/{slug}', [AnchorageController::class, 'show'])->name('anchorage');

    Route::get('croisieres', [CruiseController::class, 'index'])->name('cruises');
    Route::get('croisieres/nouvelle', [CruiseController::class, 'create'])->name('cruises.create');
    Route::post('croisieres', [CruiseController::class, 'store'])->name('cruises.store');
    Route::get('croisieres/{slug}/modifier', [CruiseController::class, 'edit'])->name('cruises.edit');
    Route::put('croisieres/{slug}', [CruiseController::class, 'update'])->name('cruises.update');
    Route::get('croisieres/{slug}', [CruiseController::class, 'show'])->name('cruise');
    Route::delete('croisieres/{slug}', [CruiseController::class, 'destroy'])->name('cruises.destroy');
    Route::post('croisieres/{slug}/itineraires', [CruiseController::class, 'generateRoutes'])->name('cruises.routes.generate');
    Route::put('croisieres/{slug}/itineraires/{scenario}', [CruiseController::class, 'selectRoute'])->name('cruises.routes.select');
    Route::get('croisieres/{slug}/carnet', [CruiseTravelController::class, 'booklet'])->name('cruises.booklet');
    Route::get('croisieres/{slug}/export.gpx', [CruiseTravelController::class, 'gpx'])->name('cruises.gpx');
    Route::patch('croisieres/{slug}/checklist/{item}', [CruiseTravelController::class, 'check'])->name('cruises.checklist');

    Route::get('bateau', [BoatController::class, 'show'])->name('boat');
    Route::get('bateau/configurer', [BoatController::class, 'setup'])->name('boat.setup');
    Route::post('bateau', [BoatController::class, 'store'])->name('boat.store');
    Route::patch('bateau/equipements', [BoatController::class, 'updateEquipment'])->name('boat.equipment.update');
    Route::post('bateaux', [BoatController::class, 'storeInFleet'])->name('boats.store');
});

// Les contributions communautaires exigent un compte, y compris en local.
Route::middleware(['auth', 'verified', 'throttle:reviews'])->group(function (): void {
    Route::post('mouillages/{slug}/avis', [AnchorageController::class, 'storeReview'])->name('anchorage.reviews.store');
    Route::post('ports/{slug}/avis', [PortController::class, 'storeReview'])->name('port.reviews.store');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('home');
    Route::get('utilisateurs', [AdminUserController::class, 'index'])->name('users');
    Route::put('utilisateurs/{user}/role', [AdminUserController::class, 'updateRole'])
        ->middleware([RequirePassword::class, 'throttle:admin-sensitive'])
        ->name('users.role.update');
    Route::get('tarifs', [PricingController::class, 'index'])->name('pricing');
    Route::put('tarifs/{plan}', [PricingController::class, 'update'])
        ->middleware([RequirePassword::class, 'throttle:admin-sensitive'])
        ->name('pricing.update');
    Route::get('avis', [AdminReviewController::class, 'index'])->name('reviews');
    Route::put('avis/{review}', [AdminReviewController::class, 'update'])
        ->middleware([RequirePassword::class, 'throttle:admin-sensitive'])
        ->name('reviews.update');
});

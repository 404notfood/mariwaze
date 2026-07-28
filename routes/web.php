<?php

use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\CruiseController;
use App\Http\Controllers\Demo\DemoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

$maritimeMiddleware = app()->environment(['local', 'testing']) ? [] : ['auth', 'verified'];

Route::middleware($maritimeMiddleware)->group(function (): void {
    Route::controller(DemoController::class)->group(function (): void {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('mouillages/{slug}', 'anchorage')->name('anchorage');
        Route::get('explorer', 'explore')->name('explore');
        Route::get('carte', 'map')->name('map');
        Route::get('securite', 'security')->name('security');
    });

    Route::get('croisieres', [CruiseController::class, 'index'])->name('cruises');
    Route::get('croisieres/nouvelle', [CruiseController::class, 'create'])->name('cruises.create');
    Route::post('croisieres', [CruiseController::class, 'store'])->name('cruises.store');
    Route::get('croisieres/{slug}/modifier', [CruiseController::class, 'edit'])->name('cruises.edit');
    Route::put('croisieres/{slug}', [CruiseController::class, 'update'])->name('cruises.update');
    Route::get('croisieres/{slug}', [CruiseController::class, 'show'])->name('cruise');
    Route::delete('croisieres/{slug}', [CruiseController::class, 'destroy'])->name('cruises.destroy');
    Route::get('bateau', [BoatController::class, 'show'])->name('boat');
    Route::get('bateau/configurer', [BoatController::class, 'setup'])->name('boat.setup');
    Route::post('bateau', [BoatController::class, 'store'])->name('boat.store');
    Route::post('bateaux', [BoatController::class, 'storeInFleet'])->name('boats.store');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('home');
    Route::get('utilisateurs', [AdminUserController::class, 'index'])->name('users');
    Route::put('utilisateurs/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role.update');
    Route::get('tarifs', [PricingController::class, 'index'])->name('pricing');
    Route::put('tarifs/{plan}', [PricingController::class, 'update'])->name('pricing.update');
});

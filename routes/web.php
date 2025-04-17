<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;

Route::get('/', function () {
    return redirect()->route('route-optimizer.index');
});

// Route Optimizer routes
Route::get('route-optimizer', [RouteOptimizerController::class, 'index'])->name('route-optimizer.index');
Route::post('route-optimizer', [RouteOptimizerController::class, 'store'])->name('route-optimizer.store');
Route::delete('route-optimizer/{location}', [RouteOptimizerController::class, 'destroy'])->name('route-optimizer.destroy');

// Route management routes
Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
Route::post('routes/generate', [RouteController::class, 'generateRoutes'])->name('routes.generate');
Route::delete('routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');

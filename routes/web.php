<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\LocationController;

Route::get('/', function () {
    return redirect()->route('route-optimizer.index');
});

// Route Optimizer routes
Route::get('route-optimizer', [RouteOptimizerController::class, 'index'])->name('route-optimizer.index');
Route::post('route-optimizer', [RouteOptimizerController::class, 'store'])->name('route-optimizer.store');
Route::delete('route-optimizer/{location}', [RouteOptimizerController::class, 'destroy'])->name('route-optimizer.destroy');

// Route management routes
Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
Route::post('routes/generate', [RouteController::class, 'generate'])->name('routes.generate');
Route::post('/routes/move-location', [RouteController::class, 'moveLocation'])->name('routes.move-location');
Route::post('/routes/recalculate', [RouteController::class, 'recalculateRoute'])->name('routes.recalculate');
Route::delete('/routes/delete-all', [RouteController::class, 'deleteAll'])->name('routes.deleteAll');
Route::delete('/locations/delete-all', [LocationController::class, 'deleteAll'])->name('locations.deleteAll');
Route::delete('routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');
Route::put('routes/{route}', [RouteController::class, 'update'])->name('routes.update');

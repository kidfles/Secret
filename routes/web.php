<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;

Route::get('/', function () {
    return redirect()->route('route-optimizer.index');
});

// Remove the resource route and define specific routes
Route::get('route-optimizer', [RouteOptimizerController::class, 'index'])->name('route-optimizer.index');
Route::post('route-optimizer', [RouteOptimizerController::class, 'store'])->name('route-optimizer.store');
Route::delete('route-optimizer/{location}', [RouteOptimizerController::class, 'destroy'])->name('route-optimizer.destroy');

Route::resource('routes', RouteController::class);
Route::post('routes/generate', [RouteController::class, 'generateRoutes'])->name('routes.generate');

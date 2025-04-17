<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;

Route::get('/', function () {
    return redirect()->route('route-optimizer.index');
});

Route::resource('route-optimizer', RouteOptimizerController::class);
Route::get('route-optimizer/{location}/destroy', [RouteOptimizerController::class, 'destroy'])->name('route-optimizer.destroy');

Route::resource('routes', RouteController::class);
Route::get('routes/generate', [RouteController::class, 'generateRoutes'])->name('routes.generate');

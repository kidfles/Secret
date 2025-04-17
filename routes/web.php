<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('route-optimizer.index');
});

Route::resource('route-optimizer', RouteOptimizerController::class)->only(['index', 'store', 'destroy']);
Route::get('route-optimizer/calculate', [RouteOptimizerController::class, 'calculateRoute'])->name('route-optimizer.calculate');

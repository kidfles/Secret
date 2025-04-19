<?php

use App\Http\Controllers\RouteOptimizerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RouteApprovalController;
use App\Http\Controllers\DayPlannerController;

Route::get('/', function () {
    return redirect()->route('day-planner.index');
});

// Route Optimizer routes
Route::middleware(['require.date'])->group(function () {
    Route::get('route-optimizer', [RouteOptimizerController::class, 'index'])->name('route-optimizer.index');
    Route::post('route-optimizer', [RouteOptimizerController::class, 'store'])->name('route-optimizer.store');
    Route::delete('route-optimizer/{location}', [RouteOptimizerController::class, 'destroy'])->name('route-optimizer.destroy');
});

// Route management routes
Route::middleware(['require.date'])->group(function () {
    Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
    Route::get('routes/create', [RouteController::class, 'create'])->name('routes.create');
    Route::post('routes', [RouteController::class, 'store'])->name('routes.store');
    Route::post('routes/generate', [RouteController::class, 'generate'])->name('routes.generate');
    Route::get('routes/generate-from-day/{date}', [RouteController::class, 'generateFromDay'])->name('routes.generate-from-day');
    Route::post('/routes/move-location', [RouteController::class, 'moveLocation'])->name('routes.move-location');
    Route::post('/routes/recalculate', [RouteController::class, 'recalculateRoute'])->name('routes.recalculate');
    Route::post('/routes/optimize-all', [RouteController::class, 'optimizeAllRoutes'])->name('routes.optimize-all');
    Route::delete('/routes/delete-all', [RouteController::class, 'deleteAll'])->name('routes.deleteAll');
    Route::delete('/locations/delete-all', [LocationController::class, 'deleteAll'])->name('locations.deleteAll');
    Route::delete('routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');
    Route::put('routes/{route}', [RouteController::class, 'update'])->name('routes.update');
});

// Route Approval routes
Route::middleware(['require.date'])->group(function () {
    Route::get('routes/approval', [RouteApprovalController::class, 'index'])->name('routes.approval.index');
    Route::get('routes/approval/create', [RouteApprovalController::class, 'create'])->name('routes.approval.create');
    Route::post('routes/approval/schedule', [RouteApprovalController::class, 'schedule'])->name('routes.approval.schedule');
    Route::get('routes/approval/{date}', [RouteApprovalController::class, 'showDate'])->name('routes.approval.show');
    Route::post('routes/approval/{date}/approve', [RouteApprovalController::class, 'approve'])->name('routes.approval.approve');
    Route::post('routes/approval/{date}/unapprove', [RouteApprovalController::class, 'unapprove'])->name('routes.approval.unapprove');
});

// Day Planner routes
Route::get('day-planner', [DayPlannerController::class, 'index'])->name('day-planner.index');
Route::get('day-planner/create', [DayPlannerController::class, 'create'])->name('day-planner.create');
Route::post('day-planner', [DayPlannerController::class, 'store'])->name('day-planner.store');
Route::get('day-planner/{date}', [DayPlannerController::class, 'show'])->name('day-planner.show')->where('date', '[^/]+');
Route::get('day-planner/{date}/edit', [DayPlannerController::class, 'edit'])->name('day-planner.edit')->where('date', '[^/]+');
Route::put('day-planner/{date}', [DayPlannerController::class, 'update'])->name('day-planner.update')->where('date', '[^/]+');
Route::delete('day-planner/{date}', [DayPlannerController::class, 'destroy'])->name('day-planner.destroy')->where('date', '[^/]+');

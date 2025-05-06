<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use App\Models\DayPlanning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DayPlannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // If date is provided in the GET request, redirect to show
        if ($request->has('date')) {
            return redirect()->route('day-planner.show', $request->date);
        }
        
        // Get all day plannings
        $plannedDays = DB::table('day_plannings')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($planning) {
                // Use either date or scheduled_date based on which one exists
                $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
                
                return [
                    'date' => $planning->date,
                    'formatted_date' => date('d-m-Y', strtotime($planning->date)),
                    'routes_count' => Route::where($dateColumn, $planning->date)->count()
                ];
            });

        return view('day-planner.index', compact('plannedDays'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('day-planner.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        // Format date for storage using strtotime for consistency
        $timestamp = strtotime($validated['date']);
        $formattedDate = date('Y-m-d', $timestamp);
        
        // Create or update a day planning record
        DayPlanning::updateOrCreate(
            ['date' => $formattedDate],
            ['notes' => $request->notes ?? null]
        );

        // Store the selected date in the session
        session(['selected_date' => $formattedDate]);

        $displayDate = date('d-m-Y', $timestamp);
        
        // Redirect to locations (route-optimizer) instead of routes
        return redirect()
            ->route('route-optimizer.index')
            ->with('success', 'Nieuwe dagplanning aangemaakt voor ' . $displayDate . '. U kunt nu locaties toevoegen.');
    }

    /**
     * Display the specified resource.
     */
    public function show($date)
    {
        // Ensure we have a standardized date format
        // First convert to timestamp to avoid timezone issues
        $timestamp = strtotime($date);
        if (!$timestamp) {
            abort(400, 'Invalid date format');
        }
        
        // Format the date in Y-m-d format for database storage
        $date = date('Y-m-d', $timestamp);
        
        // Store the selected date in the session - with proper format
        session(['selected_date' => $date]);
        
        // Log for debugging
        \Log::info('DayPlannerController@show - Setting selected_date: ' . $date);
        
        // Find or create the day planning
        $dayPlanning = DayPlanning::firstOrCreate(['date' => $date]);
        
        // Use either date or scheduled_date based on which one exists
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        
        $routes = Route::where($dateColumn, $date)->orderBy('created_at', 'asc')->get();
        
        // Get previous and next planned days
        $prevDate = DayPlanning::where('date', '<', $date)->orderBy('date', 'desc')->first()?->date;
        $nextDate = DayPlanning::where('date', '>', $date)->orderBy('date', 'asc')->first()?->date;
        
        return view('day-planner.show', compact('date', 'routes', 'prevDate', 'nextDate', 'dayPlanning'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($date)
    {
        // Debug the date parameter
        if (empty($date)) {
            abort(400, 'Missing date parameter');
        }
        
        // Ensure we have a standardized date format
        $timestamp = strtotime($date);
        if (!$timestamp) {
            abort(400, 'Invalid date format');
        }
        
        $date = date('Y-m-d', $timestamp);
        
        // Store the selected date in the session
        session(['selected_date' => $date]);
        
        // Log for debugging
        \Log::info('DayPlannerController@edit - Setting selected_date: ' . $date);
        
        // Find or create the day planning
        $dayPlanning = DayPlanning::firstOrCreate(['date' => $date]);
        
        // Use either date or scheduled_date based on which one exists
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        
        $routes = Route::where($dateColumn, $date)->orderBy('created_at', 'asc')->get();
        
        return view('day-planner.edit', compact('date', 'routes', 'dayPlanning'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $date)
    {
        $request->validate([
            'new_date' => 'required|date',
        ]);

        $oldTimestamp = strtotime($date);
        $newTimestamp = strtotime($request->new_date);
        
        $oldDate = date('Y-m-d', $oldTimestamp);
        $newDate = date('Y-m-d', $newTimestamp);

        try {
            DB::beginTransaction();
            
            // Update or create day planning record for the new date
            DayPlanning::updateOrCreate(
                ['date' => $newDate],
                ['notes' => $request->notes ?? null]
            );
            
            // Use either date or scheduled_date based on which one exists
            $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
            
            $routes = Route::where($dateColumn, $oldDate)->get();
            
            foreach ($routes as $route) {
                $route->$dateColumn = $newDate;
                $route->save();
            }
            
            // If no routes remain for the old date and it's different from the new date,
            // delete the old day planning
            if ($oldDate !== $newDate) {
                $oldPlanning = DayPlanning::where('date', $oldDate)->first();
                if ($oldPlanning && Route::where($dateColumn, $oldDate)->count() === 0) {
                    $oldPlanning->delete();
                }
            }
            
            DB::commit();
            
            // Update the selected date in the session
            session(['selected_date' => $newDate]);
            
            return redirect()->route('day-planner.show', $newDate)
                ->with('success', 'Dagplanning is bijgewerkt en verplaatst naar ' . date('d-m-Y', $newTimestamp));
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($date)
    {
        $timestamp = strtotime($date);
        $date = date('Y-m-d', $timestamp);
        
        try {
            DB::beginTransaction();
            
            // Delete the day planning record
            DayPlanning::where('date', $date)->delete();
            
            // Use either date or scheduled_date based on which one exists
            $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
            
            $routes = Route::where($dateColumn, $date)->get();
            
            foreach ($routes as $route) {
                $route->delete();
            }
            
            DB::commit();
            
            return redirect()->route('day-planner.index')
                ->with('success', 'Dagplanning voor ' . date('d-m-Y', $timestamp) . ' is verwijderd');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Er is een fout opgetreden: ' . $e->getMessage());
        }
    }
} 
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
        $plannedDays = DayPlanning::orderBy('date', 'asc')
            ->get()
            ->map(function ($planning) {
                // Use either date or scheduled_date based on which one exists
                $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
                
                return [
                    'date' => $planning->date,
                    'formatted_date' => Carbon::parse($planning->date)->format('d-m-Y'),
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

        // Format date for storage
        $formattedDate = Carbon::parse($validated['date'])->format('Y-m-d');
        
        // Create or update a day planning record
        DayPlanning::updateOrCreate(
            ['date' => $formattedDate],
            ['notes' => $request->notes ?? null]
        );

        // Store the selected date in the session
        session(['selected_date' => $formattedDate]);

        $displayDate = Carbon::parse($formattedDate)->format('d-m-Y');
        
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
        $date = Carbon::parse($date)->format('Y-m-d');
        
        // Store the selected date in the session
        session(['selected_date' => $date]);
        
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
        
        $date = Carbon::parse($date)->format('Y-m-d');
        
        // Store the selected date in the session
        session(['selected_date' => $date]);
        
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

        $oldDate = Carbon::parse($date)->format('Y-m-d');
        $newDate = Carbon::parse($request->new_date)->format('Y-m-d');

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
                ->with('success', 'Dagplanning is bijgewerkt en verplaatst naar ' . Carbon::parse($newDate)->format('d-m-Y'));
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
        $date = Carbon::parse($date)->format('Y-m-d');
        
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
                ->with('success', 'Dagplanning voor ' . Carbon::parse($date)->format('d-m-Y') . ' is verwijderd');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Er is een fout opgetreden: ' . $e->getMessage());
        }
    }
} 
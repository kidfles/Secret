<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        
        $plannedDays = Route::select('date')
            ->distinct()
            ->whereNotNull('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'formatted_date' => Carbon::parse($item->date)->format('d-m-Y'),
                    'routes_count' => Route::where('date', $item->date)->count()
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

        // Check if we already have routes for this date
        $existingRoutes = Route::whereDate('start_date', $validated['date'])->count();
        if ($existingRoutes > 0) {
            return redirect()
                ->route('day-planner.edit', $validated['date'])
                ->with('info', 'Er zijn al routes gepland voor deze datum.');
        }

        return redirect()
            ->route('day-planner.edit', $validated['date'])
            ->with('success', 'Nieuwe dagplanning aangemaakt.');
    }

    /**
     * Display the specified resource.
     */
    public function show($date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        
        // Store the selected date in the session
        session(['selected_date' => $date]);
        
        $routes = Route::where('date', $date)->orderBy('created_at', 'asc')->get();
        
        $prevDate = Route::where('date', '<', $date)->orderBy('date', 'desc')->first()?->date;
        $nextDate = Route::where('date', '>', $date)->orderBy('date', 'asc')->first()?->date;
        
        return view('day-planner.show', compact('date', 'routes', 'prevDate', 'nextDate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        
        $routes = Route::where('date', $date)->orderBy('created_at', 'asc')->get();
        
        return view('day-planner.edit', compact('date', 'routes'));
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
            
            $routes = Route::where('date', $oldDate)->get();
            
            foreach ($routes as $route) {
                $route->date = $newDate;
                $route->save();
            }
            
            DB::commit();
            
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
            
            $routes = Route::where('date', $date)->get();
            
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
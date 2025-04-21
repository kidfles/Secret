<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class RouteOptimizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get selected date from session
        $selectedDate = session('selected_date');
        
        // Debug: Log the selected date to see what's being used
        \Log::info('RouteOptimizer - Selected Date: ' . ($selectedDate ?? 'NULL'));
        
        // Get all locations first but filtered by date if available
        $locationsQuery = Location::orderBy('name');
        if ($selectedDate) {
            // Ensure the selected date is refreshed in the session with each view
            session(['selected_date' => $selectedDate]);
            
            // Use the new scope for consistency
            $locationsQuery->forDate($selectedDate);
        }
        $allLocations = $locationsQuery->get();
        
        // Use either date or scheduled_date based on which one exists
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        
        if ($selectedDate) {
            // If we have a date filter, show only unassigned locations and 
            // locations assigned to routes with this date
            $locationsForDate = Location::whereHas('routes', function($query) use ($selectedDate, $dateColumn) {
                $query->whereDate($dateColumn, $selectedDate);
            })->orderBy('name')->get();
            
            // Only show unassigned locations that match the date filter
            // Use our scope for consistency
            $unassignedLocations = Location::whereDoesntHave('routes')
                ->forDate($selectedDate)
                ->orderBy('name')
                ->get();
            
            // Combine both collections
            $locations = $locationsForDate->merge($unassignedLocations);
            
            // Format date for display - use PHP's date function to avoid timezone issues
            $formattedDate = date('d-m-Y', strtotime($selectedDate));
            \Log::info('RouteOptimizer - Formatted Date: ' . $formattedDate);
            
            // Pass the raw selected date for debugging
            $rawSelectedDate = $selectedDate;
            
            return view('route-optimizer.index', compact('locations', 'allLocations', 'selectedDate', 'formattedDate', 'rawSelectedDate'));
        } else {
            // If no date filter, show all locations
            $locations = $allLocations;
            return view('route-optimizer.index', compact('locations', 'allLocations'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'street' => 'required|string|max:255',
                'house_number' => 'required|string|max:10',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'person_capacity' => 'required|integer|min:1',
                'tegels' => 'nullable|integer|min:0',
                'tegels_type' => 'nullable|string|in:pix25,pix100,vlakled,patroon',
                'begin_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after_or_equal:begin_time',
                'completion_minutes' => 'nullable|integer|min:0',
                'date' => 'nullable|date',
            ]);

            // Convert empty strings to null for time fields
            if (empty($validated['begin_time'])) {
                $validated['begin_time'] = null;
            }
            
            if (empty($validated['end_time'])) {
                $validated['end_time'] = null;
            }
            
            // If date is not provided but selected_date is in session, use that
            if (empty($validated['date']) && session()->has('selected_date')) {
                $validated['date'] = session('selected_date');
            }
            
            // If tegels is empty or zero, ensure it's set to zero
            $validated['tegels'] = $validated['tegels'] ?? 0;
            
            // If tegels is zero, clear tegels_type
            if ($validated['tegels'] == 0) {
                $validated['tegels_type'] = null;
            }
            
            // Auto-calculate completion_minutes if not provided
            if (empty($validated['completion_minutes']) && $validated['tegels'] > 0) {
                $baseDuration = 40; // Base 40 minutes
                $additionalTime = ceil($validated['tegels'] * 1.5); // 1.5 minutes per tegel, rounded up
                $validated['completion_minutes'] = $baseDuration + $additionalTime;
            }

            // Generate address field automatically
            $validated['address'] = $validated['street'] . ' ' . $validated['house_number'] . ', ' . $validated['city'];

            // For backward compatibility with older code, set tegels_count to the same value as tegels
            $validated['tegels_count'] = $validated['tegels'];

            $location = Location::create($validated);

            // Clear all route-related caches to ensure fresh data
            Cache::forget('routes');
            Cache::forget('routes_index');

            return redirect()->route('route-optimizer.index')
                ->with('success', 'Location added successfully!');
        } catch (\Exception $e) {
            return redirect()->route('route-optimizer.index')
                ->with('error', 'Error adding location: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $location = Location::findOrFail($id);
        return view('route-optimizer.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $location = Location::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'street' => 'required|string|max:255',
                'house_number' => 'required|string|max:10',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'person_capacity' => 'required|integer|min:1',
                'tegels' => 'nullable|integer|min:0',
                'tegels_type' => 'nullable|string|in:pix25,pix100,vlakled,patroon',
                'begin_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after_or_equal:begin_time',
                'completion_minutes' => 'nullable|integer|min:0',
                'date' => 'nullable|date',
            ]);

            // Convert empty strings to null for time fields
            if (empty($validated['begin_time'])) {
                $validated['begin_time'] = null;
            }
            
            if (empty($validated['end_time'])) {
                $validated['end_time'] = null;
            }
            
            // If tegels is empty or zero, ensure it's set to zero
            $validated['tegels'] = $validated['tegels'] ?? 0;
            
            // If tegels is zero, clear tegels_type
            if ($validated['tegels'] == 0) {
                $validated['tegels_type'] = null;
            }
            
            // Auto-calculate completion_minutes if not provided
            if (empty($validated['completion_minutes']) && $validated['tegels'] > 0) {
                $baseDuration = 40; // Base 40 minutes
                $additionalTime = ceil($validated['tegels'] * 1.5); // 1.5 minutes per tegel, rounded up
                $validated['completion_minutes'] = $baseDuration + $additionalTime;
            }

            // Generate address field automatically
            $validated['address'] = $validated['street'] . ' ' . $validated['house_number'] . ', ' . $validated['city'];

            // For backward compatibility with older code, set tegels_count to the same value as tegels
            $validated['tegels_count'] = $validated['tegels'];

            $location->update($validated);

            // Clear all route-related caches to ensure fresh data
            Cache::forget('routes');
            Cache::forget('routes_index');

            return redirect()->route('route-optimizer.index')
                ->with('success', 'Location updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('route-optimizer.index')
                ->with('error', 'Error updating location: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        try {
            DB::beginTransaction();
            
            // Remove the location from any routes
            $location->routes()->detach();
            
            // Delete the location
            $location->delete();
            
            DB::commit();
            
            Cache::forget('routes');
            
            return redirect()->route('route-optimizer.index')
                ->with('success', 'Location deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('route-optimizer.index')
                ->with('error', 'Error deleting location: ' . $e->getMessage());
        }
    }
}

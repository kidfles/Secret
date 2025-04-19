<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RouteOptimizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::orderBy('name')->get();
        return view('route-optimizer.index', compact('locations'));
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
                'tegels_type' => 'nullable|string|in:pix100,pix25,vlakled,patroon',
                'begin_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after_or_equal:begin_time',
                'completion_minutes' => 'nullable|integer|min:0',
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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

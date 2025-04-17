<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Delete all locations.
     */
    public function deleteAll()
    {
        try {
            DB::beginTransaction();
            
            // Delete all route relationships first
            DB::table('route_location')->delete();
            // Delete all routes
            DB::table('routes')->delete();
            // Delete all locations
            Location::truncate();
            
            DB::commit();
            return redirect()->route('routes.index')->with('success', 'Alle locaties en routes zijn verwijderd.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Fout bij het verwijderen van locaties: ' . $e->getMessage());
        }
    }

    public function destroy(Location $location)
    {
        try {
            DB::beginTransaction();
            
            // Delete the location's relationships with routes
            DB::table('route_location')->where('location_id', $location->id)->delete();
            
            // Delete the location
            $location->delete();
            
            DB::commit();
            return redirect()->route('route-optimizer.index')->with('success', 'Locatie succesvol verwijderd.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting location: ' . $e->getMessage());
        }
    }
} 
<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RouteApprovalController extends Controller
{
    /**
     * Display a listing of the route approvals.
     */
    public function index(Request $request)
    {
        $query = DB::table('routes')
            ->select(
                'date',
                DB::raw('COUNT(*) as routes_count'),
                DB::raw('SUM((SELECT COUNT(*) FROM locations WHERE locations.route_id = routes.id)) as locations_count'),
                DB::raw('SUM((SELECT SUM(tegels) FROM locations WHERE locations.route_id = routes.id)) as tiles_count'),
                DB::raw('MIN(is_approved) = MAX(is_approved) AND MIN(is_approved) = 1 as is_approved'),
                DB::raw('MAX(updated_at) as updated_at')
            )
            ->whereNotNull('date')
            ->groupBy('date');

        // Apply filters
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'approved') {
                $query->havingRaw('MIN(is_approved) = 1');
            } elseif ($request->status === 'pending') {
                $query->havingRaw('MIN(is_approved) = 0');
            }
        }

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereRaw('MONTH(date) = ?', [$request->month]);
        }

        if ($request->filled('year')) {
            $query->whereRaw('YEAR(date) = ?', [$request->year]);
        }

        $approvals = $query->orderBy('date', 'desc')->paginate(10);

        return view('routes.approval.index', compact('approvals'));
    }

    /**
     * Show the form for creating a new route approval.
     */
    public function create()
    {
        // Get all routes without a date assigned
        $unscheduledRoutes = Route::whereNull('date')->get();
        
        return view('routes.approval.create', compact('unscheduledRoutes'));
    }

    /**
     * Schedule routes for a specific date.
     */
    public function schedule(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'route_ids' => 'required|array',
            'route_ids.*' => 'exists:routes,id'
        ]);

        // Check if any routes are already assigned to this date
        if (Route::where('date', $request->date)->exists()) {
            return redirect()->route('routes.approval.create')
                ->with('error', 'Er zijn al routes gepland voor deze datum. Kies een andere datum.');
        }

        // Assign the date to the selected routes
        Route::whereIn('id', $request->route_ids)->update([
            'date' => $request->date,
            'is_approved' => false
        ]);

        return redirect()->route('routes.approval.show', $request->date)
            ->with('success', 'Routes zijn succesvol gepland voor ' . Carbon::parse($request->date, 'Europe/Amsterdam')->format('d-m-Y') . '.');
    }

    /**
     * Store a newly created route approval in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'routes' => 'required|array',
            'routes.*' => 'exists:routes,id'
        ]);

        // Check if any routes are already assigned to this date
        if (Route::where('date', $request->date)->exists()) {
            return redirect()->route('routes.approval.create')
                ->with('error', 'Er zijn al routes gepland voor deze datum. Kies een andere datum.');
        }

        // Assign the date to the selected routes
        Route::whereIn('id', $request->routes)->update([
            'date' => $request->date,
            'is_approved' => false
        ]);

        return redirect()->route('routes.approval.show', $request->date)
            ->with('success', 'Routes zijn succesvol gepland voor ' . Carbon::parse($request->date, 'Europe/Amsterdam')->format('d-m-Y') . '.');
    }

    /**
     * Display the specified route approval.
     */
    public function showDate($date)
    {
        // Validate date format
        try {
            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return redirect()->route('routes.approval.index')
                ->with('error', 'Ongeldige datum.');
        }

        // Get routes for the specified date
        $routes = Route::with(['locations' => function($query) {
            $query->orderBy('order', 'asc');
        }])
        ->where('date', $date)
        ->get();

        if ($routes->isEmpty()) {
            return redirect()->route('routes.approval.index')
                ->with('error', 'Geen routes gevonden voor deze datum.');
        }

        // Calculate totals
        $totalLocations = $routes->sum(function($route) {
            return $route->locations->count();
        });
        
        $totalTiles = $routes->sum(function($route) {
            return $route->locations->sum('tegels');
        });

        $isApproved = $routes->every(function($route) {
            return $route->is_approved;
        });

        $approvedBy = null;
        $approvedAt = null;
        
        if ($isApproved) {
            $approvedRoute = $routes->first();
            $approvedBy = $approvedRoute->approved_by;
            $approvedAt = $approvedRoute->approved_at;
        }

        return view('routes.approval.show', compact(
            'routes', 
            'date', 
            'totalLocations', 
            'totalTiles', 
            'isApproved',
            'approvedBy',
            'approvedAt'
        ));
    }

    /**
     * Approve all routes for a specific date.
     */
    public function approve($date, Request $request)
    {
        $routes = Route::where('date', $date)->get();
        
        if ($routes->isEmpty()) {
            return redirect()->route('routes.approval.index')
                ->with('error', 'Geen routes gevonden voor deze datum.');
        }
        
        // Update all routes to approved status
        Route::where('date', $date)->update([
            'is_approved' => true,
            'approved_by' => auth()->user()->name,
            'approved_at' => now()
        ]);
        
        return redirect()->route('routes.approval.show', $date)
            ->with('success', 'Alle routes voor deze datum zijn goedgekeurd.');
    }

    /**
     * Unapprove all routes for a specific date.
     */
    public function unapprove($date)
    {
        $routes = Route::where('date', $date)->get();
        
        if ($routes->isEmpty()) {
            return redirect()->route('routes.approval.index')
                ->with('error', 'Geen routes gevonden voor deze datum.');
        }
        
        // Update all routes to unapproved status
        Route::where('date', $date)->update([
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null
        ]);
        
        return redirect()->route('routes.approval.show', $date)
            ->with('success', 'Goedkeuring van routes voor deze datum is ongedaan gemaakt.');
    }

    /**
     * Remove all routes from the specified date (without deleting the routes).
     */
    public function destroy($date)
    {
        // Find routes for the specified date
        $routes = Route::where('date', $date)->get();
        
        if ($routes->isEmpty()) {
            return redirect()->route('routes.approval.index')
                ->with('error', 'Geen routes gevonden voor deze datum.');
        }
        
        // Unassign routes from this date
        Route::where('date', $date)->update([
            'date' => null,
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null
        ]);
        
        return redirect()->route('routes.approval.index')
            ->with('success', 'Routes voor ' . Carbon::parse($date, 'Europe/Amsterdam')->format('d-m-Y') . ' zijn verwijderd uit de planning.');
    }
} 
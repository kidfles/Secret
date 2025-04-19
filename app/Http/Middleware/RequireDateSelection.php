<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDateSelection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the user has selected a date (stored in session), allow access
        if ($request->session()->has('selected_date')) {
            return $next($request);
        }
        
        // Otherwise redirect to day planner with a message
        return redirect()->route('day-planner.index')
            ->with('error', 'Selecteer eerst een datum voordat je deze pagina bezoekt.');
    }
} 
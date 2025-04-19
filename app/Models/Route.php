<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_location_id',
        'end_location_id',
        'start_time', // Default start time for the route
        'scheduled_date',
        'date', // Added the date field for the new workflow
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'scheduled_date' => 'date',
        'date' => 'date',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the total estimated duration for this route
     * Including travel time and location completion time
     * 
     * @return int Total duration in minutes
     */
    public function getTotalDurationAttribute(): int
    {
        $totalMinutes = 0;
        
        // Get ordered locations with travel and completion times
        $locations = $this->locations()
            ->orderBy('route_location.order')
            ->get();
            
        foreach ($locations as $location) {
            // Add travel time if available
            if ($location->pivot->travel_time) {
                $totalMinutes += $location->pivot->travel_time;
            }
            
            // Add completion time
            $totalMinutes += $location->completion_time;
        }
        
        return $totalMinutes;
    }
    
    /**
     * Get the total distance of the route in kilometers
     *
     * @return float
     */
    public function getTotalDistanceAttribute(): float
    {
        $locations = $this->locations()
            ->orderBy('route_location.order')
            ->get();
            
        $totalDistance = 0;
        $prevLat = 51.8372; // Default starting coordinates
        $prevLng = 5.6697;
        
        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $prevLat, $prevLng,
                $location->latitude, $location->longitude
            );
            $totalDistance += $distance;
            
            $prevLat = $location->latitude;
            $prevLng = $location->longitude;
        }
        
        // Add return to start
        $totalDistance += $this->calculateDistance(
            $prevLat, $prevLng,
            51.8372, 5.6697
        );
        
        return round($totalDistance, 2);
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371; // Earth's radius in km
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'route_location')
            ->withPivot('order', 'arrival_time', 'completion_time', 'travel_time')
            ->orderBy('route_location.order');
    }

    public function startLocation()
    {
        return $this->belongsTo(Location::class, 'start_location_id');
    }

    public function endLocation()
    {
        return $this->belongsTo(Location::class, 'end_location_id');
    }
} 
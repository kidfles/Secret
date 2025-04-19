<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'street',
        'house_number',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'person_capacity',
        'address',
        'tegels_count',
        'tegels_type',
        'begin_time',
        'end_time',
        'completion_minutes',
        'tegels',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'tegels_count' => 'integer',
        'completion_minutes' => 'integer',
        'begin_time' => 'datetime',
        'end_time' => 'datetime',
        'tegels' => 'integer',
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'tegels_count' => 'nullable|integer|min:0|max:100',
            'tegels_type' => 'nullable|string|in:pix25,pix100,vlakled,patroon',
            'begin_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:begin_time',
            'tegels' => 'nullable|integer|min:0',
            'completion_minutes' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Calculate the estimated duration to complete this location
     * Formula: 40 minutes + (2 × number of tegels)
     *
     * @return int Duration in minutes
     */
    public function getCompletionTimeAttribute(): int
    {
        // If there's a manually set duration, return that
        if ($this->completion_minutes) {
            return $this->completion_minutes;
        }
        
        // Otherwise calculate based on the formula
        $baseDuration = 40; // Base 40 minutes
        
        // Use the new tegels field if available, otherwise fall back to tegels_count
        $tegelCount = $this->tegels ?? $this->tegels_count ?? 0;
        $additionalTime = ceil($tegelCount * 2); // 2 minutes per tegel, rounded up
        
        return $baseDuration + $additionalTime;
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class)
            ->withPivot('order', 'arrival_time', 'completion_time', 'travel_time')
            ->orderBy('route_location.order');
    }
}

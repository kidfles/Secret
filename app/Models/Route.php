<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Route extends Model
{
    protected $fillable = [
        'name',
        'capacity'
    ];

    protected $casts = [
        'capacity' => 'integer'
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'route_location')
            ->withPivot('order')
            ->orderBy('route_location.order');
    }
} 
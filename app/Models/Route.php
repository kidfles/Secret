<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_location_id',
        'end_location_id',
    ];

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'route_location')
            ->withPivot('order')
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'name',
        'description',
        'person_capacity',
    ];

    public function locations()
    {
        return $this->belongsToMany(Location::class)
            ->withPivot('order')
            ->orderBy('route_location.order');
    }
} 
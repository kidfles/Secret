<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'person_capacity'
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'person_capacity' => 'required|integer|min:1'
    ];

    public function routes()
    {
        return $this->belongsToMany(Route::class)
            ->withPivot('order')
            ->orderBy('route_location.order');
    }
}

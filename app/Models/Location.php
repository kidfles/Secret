<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'street',
        'house_number',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'person_capacity',
        'address'
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'street' => 'required|string|max:255',
        'house_number' => 'required|string|max:20',
        'city' => 'required|string|max:255',
        'postal_code' => 'nullable|string|max:10',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'person_capacity' => 'required|integer|min:1',
        'address' => 'required|string|max:255'
    ];

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'route_location')
            ->withPivot('order')
            ->orderBy('route_location.order');
    }
}

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
        'notes'
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'notes' => 'nullable|string'
    ];
}

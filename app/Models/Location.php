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
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
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
        ];
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class)
            ->withPivot('order')
            ->orderBy('route_location.order');
    }
}

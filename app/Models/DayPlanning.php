<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class DayPlanning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the routes associated with this day planning.
     */
    public function routes(): HasMany
    {
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        return $this->hasMany(Route::class, $dateColumn, 'date');
    }
} 
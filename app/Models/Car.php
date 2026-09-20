<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'year',
        'type',
        'seats',
        'price_per_day',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'seats' => 'integer',
        'price_per_day' => 'decimal:2',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
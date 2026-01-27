<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'plate',
        'model',
        'is_active',
        'is_available',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pallet extends Model
{
    protected $fillable = [
        'pallet_code',
        'warehouse_location',
        'status',
        'max_packages_capacity',
    ];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}

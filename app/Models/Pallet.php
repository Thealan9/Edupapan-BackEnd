<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pallet extends Model
{
    use SoftDeletes;

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
    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class, 'moved_to_pallet');
    }
}

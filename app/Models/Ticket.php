<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'assigned_to',
        'vehicle_id',
        'details',
        'quantity',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function details()
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }
}

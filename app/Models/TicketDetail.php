<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDetail extends Model
{
    protected $fillable = [
        'ticket_id',
        'package_id',
        'status',
        'moved_to_pallet',
        'description',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDetail extends Model
{
    protected $fillable = [
        'ticket_id',
        'package_id',
        'parent_id',
        'status',
        'moved_to_pallet',
        'description',
        'price',
        'book_quantity'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    public function pallet()
    {
        return $this->belongsTo(Pallet::class, 'moved_to_pallet');
    }
    public function parent()
    {
        return $this->belongsTo(TicketDetail::class, 'parent_id');
    }

    public function replacements()
    {
        return $this->hasMany(TicketDetail::class, 'parent_id');
    }
}

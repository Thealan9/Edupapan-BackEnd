<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'batch_number',
        'book_id',
        'pallet_id',
        'book_quantity',
        'status',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function pallet()
    {
        return $this->belongsTo(Pallet::class);
    }

    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class);
    }
}

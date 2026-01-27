<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
   use SoftDeletes;

    protected $fillable = [
        'title',
        'isbn',
        'level',
        'price',
        'supplier',
    ];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }
}

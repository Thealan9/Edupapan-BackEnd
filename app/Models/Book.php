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
        'quantity',
        'description',
        'autor',
        'active',
        'pages',
        'year',
        'edition',
        'format',
        'size'
    ];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

}

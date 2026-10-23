<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'stock',
        'startingStock',
        'received',
        'expirationDate',
    ];

    protected $casts = [
        'startingStock' => 'integer',
        'received' => 'datetime',
        'expirationDate' => 'datetime',
    ];
}

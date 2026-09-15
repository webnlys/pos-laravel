<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'name',
        'rate_percent',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }
}

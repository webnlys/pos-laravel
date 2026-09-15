<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleTax extends Model
{
    protected $fillable = [
        'sale_id',
        'name',
        'rate_percent',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

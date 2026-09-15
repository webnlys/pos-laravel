<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationTax extends Model
{
    protected $fillable = [
        'quotation_id',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}

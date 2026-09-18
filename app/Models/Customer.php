<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return array{charged:float, paid:float, due:float, advance:float}
     */
    public function accountSummary(): array
    {
        $charged = $this->relationLoaded('sales')
            ? (float) $this->sales->sum('total')
            : (float) ($this->sales_sum_total ?? $this->sales()->sum('total'));

        $paid = $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : (float) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));

        $charged = round($charged, 2);
        $paid = round($paid, 2);

        return [
            'charged' => $charged,
            'paid' => $paid,
            'due' => round(max(0, $charged - $paid), 2),
            'advance' => round(max(0, $paid - $charged), 2),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'number',
        'customer_id',
        'user_id',
        'quotation_id',
        'document_datetime',
        'subtotal',
        'discount',
        'tax_total',
        'total',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_datetime' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(SaleTax::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        $paid = $this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount');

        return round((float) $paid, 2);
    }

    public function dueAmount(): float
    {
        return $this->paymentSummary()['due'];
    }

    public function advanceAmount(): float
    {
        return $this->paymentSummary()['advance'];
    }

    public function paymentStatus(): string
    {
        return $this->paymentSummary()['status'];
    }

    /**
     * @return array{paid:float, due:float, advance:float, status:string}
     */
    public function paymentSummary(): array
    {
        $paid = $this->paidAmount();
        $total = round((float) $this->total, 2);
        $due = round(max(0, $total - $paid), 2);
        $advance = round(max(0, $paid - $total), 2);

        $status = 'unpaid';
        if ($paid > 0) {
            $status = $advance > 0 ? 'advance' : ($due > 0 ? 'partial' : 'paid');
        }

        return [
            'paid' => $paid,
            'due' => $due,
            'advance' => $advance,
            'status' => $status,
        ];
    }
}

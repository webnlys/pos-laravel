<?php

namespace App\Services;

use App\Models\Tax;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TaxService
{
    public function applicableAt(CarbonInterface $at): Collection
    {
        return Tax::query()
            ->where('effective_from', '<=', $at)
            ->where(function ($query) use ($at) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $at);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, array{quantity:int|float, unit_price:int|float}>  $items
     * @return array{subtotal:float, discount:float, tax_total:float, total:float, taxes:array<int, array{name:string, rate_percent:float, amount:float}>}
     */
    public function totals(array $items, float $discount, CarbonInterface $at): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += round(((float) $item['quantity']) * ((float) $item['unit_price']), 2);
        }
        $subtotal = round($subtotal, 2);
        $discount = round(max(0, $discount), 2);
        $taxable = max(0, round($subtotal - $discount, 2));

        $taxes = $this->applicableAt($at)->map(function (Tax $tax) use ($taxable) {
            return [
                'name' => $tax->name,
                'rate_percent' => (float) $tax->rate_percent,
                'amount' => round($taxable * ((float) $tax->rate_percent) / 100, 2),
            ];
        })->values()->all();

        $taxTotal = round(collect($taxes)->sum('amount'), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_total' => $taxTotal,
            'total' => round($taxable + $taxTotal, 2),
            'taxes' => $taxes,
        ];
    }
}

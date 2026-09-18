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

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     subtotal:float,
     *     discount:float,
     *     overall_discount:float,
     *     tax_mode:string,
     *     overall_tax_id:?int,
     *     overall_tax_name:?string,
     *     overall_tax_rate_percent:?float,
     *     tax_total:float,
     *     total:float,
     *     taxes:array<int, array{name:string, rate_percent:float, amount:float}>,
     *     items:array<int, array<string, mixed>>
     * }
     */
    public function quotationTotals(array $items, string $taxMode = 'per_item', float $overallDiscount = 0, ?int $overallTaxId = null): array
    {
        $taxesById = Tax::query()->get()->keyBy('id');

        $subtotal = 0.0;
        $lineDiscountTotal = 0.0;
        $perItemTaxTotal = 0.0;
        $lines = [];
        $taxRollup = [];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $gross = round($quantity * $unitPrice, 2);
            $discount = round(min(max(0, (float) ($item['discount'] ?? 0)), $gross), 2);
            $net = round($gross - $discount, 2);

            $taxId = $taxMode === 'per_item' ? ($item['tax_id'] ?? null) : null;
            $tax = $taxId ? $taxesById->get((int) $taxId) : null;
            $taxRate = $tax ? (float) $tax->rate_percent : null;
            $taxAmount = $tax ? round($net * $taxRate / 100, 2) : 0.0;
            $amount = round($net + $taxAmount, 2);

            $subtotal += $gross;
            $lineDiscountTotal += $discount;
            $perItemTaxTotal += $taxAmount;

            if ($tax) {
                $key = (int) $tax->id;
                if (! isset($taxRollup[$key])) {
                    $taxRollup[$key] = [
                        'name' => $tax->name,
                        'rate_percent' => $taxRate,
                        'amount' => 0.0,
                    ];
                }
                $taxRollup[$key]['amount'] = round($taxRollup[$key]['amount'] + $taxAmount, 2);
            }

            $lines[] = array_merge($item, [
                'discount' => $discount,
                'tax_id' => $tax?->id,
                'tax_name' => $tax?->name,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $amount,
            ]);
        }

        $subtotal = round($subtotal, 2);
        $lineDiscountTotal = round($lineDiscountTotal, 2);
        $perItemTaxTotal = round($perItemTaxTotal, 2);

        $baseAfterLineDiscount = max(0, round($subtotal - $lineDiscountTotal, 2));
        $overallDiscount = round(min(max(0, $overallDiscount), $baseAfterLineDiscount), 2);
        $discountTotal = round($lineDiscountTotal + $overallDiscount, 2);
        $taxableTotal = max(0, round($subtotal - $discountTotal, 2));

        $overallTax = $taxMode === 'overall' && $overallTaxId ? $taxesById->get($overallTaxId) : null;

        $taxTotal = match ($taxMode) {
            'per_item' => $perItemTaxTotal,
            'overall' => $overallTax ? round($taxableTotal * ((float) $overallTax->rate_percent) / 100, 2) : 0.0,
            default => 0.0,
        };

        if ($taxMode === 'overall' && $overallTax) {
            $taxRollup = [[
                'name' => $overallTax->name,
                'rate_percent' => (float) $overallTax->rate_percent,
                'amount' => $taxTotal,
            ]];
        } elseif ($taxMode !== 'per_item') {
            $taxRollup = [];
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discountTotal,
            'overall_discount' => $overallDiscount,
            'tax_mode' => $taxMode,
            'overall_tax_id' => $overallTax?->id,
            'overall_tax_name' => $overallTax?->name,
            'overall_tax_rate_percent' => $overallTax ? (float) $overallTax->rate_percent : null,
            'tax_total' => $taxTotal,
            'total' => round($taxableTotal + $taxTotal, 2),
            'taxes' => array_values($taxRollup),
            'items' => $lines,
        ];
    }
}
<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DocumentItemNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $items, bool $useSalePrice = true): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'At least one line item is required.',
            ]);
        }

        $normalized = [];

        foreach ($items as $index => $item) {
            $product = Product::query()->find($item['product_id'] ?? null);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'The selected product is invalid.',
                ]);
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'Quantity must be at least 1.',
                ]);
            }

            $unitPrice = array_key_exists('unit_price', $item)
                ? (float) $item['unit_price']
                : (float) ($useSalePrice ? $product->sale_price : $product->cost_price);
            $unitCost = array_key_exists('unit_cost', $item)
                ? (float) $item['unit_cost']
                : (float) $product->cost_price;

            $normalized[] = [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => round($unitPrice, 2),
                'unit_cost' => round($unitCost, 2),
                'discount' => round(max(0, (float) ($item['discount'] ?? 0)), 2),
                'tax_id' => $item['tax_id'] ?? null,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $normalized;
    }

    public function parseDatetime(mixed $value): Carbon
    {
        return $value ? Carbon::parse($value) : now();
    }
}

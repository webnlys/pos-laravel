<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function decrement(Product $product, int $qty): void
    {
        $product->refresh();

        if ($product->stock_qty < $qty) {
            throw ValidationException::withMessages([
                'items' => "Insufficient stock for {$product->name} ({$product->sku}). Available: {$product->stock_qty}.",
            ]);
        }

        $product->decrement('stock_qty', $qty);
    }

    public function increment(Product $product, int $qty): void
    {
        $product->increment('stock_qty', $qty);
    }

    public function applyIncomingCost(Product $product, int $qty, float $unitCost): void
    {
        $product->refresh();
        $oldQty = (int) $product->stock_qty;
        $oldCost = (float) $product->cost_price;
        $newQty = $oldQty + $qty;

        if ($newQty > 0) {
            $product->cost_price = round((($oldQty * $oldCost) + ($qty * $unitCost)) / $newQty, 2);
        } else {
            $product->cost_price = round($unitCost, 2);
        }

        $product->save();
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private StockService $stock,
        private DocumentItemNormalizer $normalizer,
    ) {}

    public function create(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $this->normalizer->normalize($data['items'], useSalePrice: false);
            $this->applyPurchasePrices($items, $data['items']);
            $subtotal = round(collect($items)->sum('line_total'), 2);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? null);

            $purchase = Purchase::query()->create([
                'number' => $this->numbers->next('PO', Purchase::class),
                'supplier_id' => $data['supplier_id'],
                'user_id' => $userId,
                'document_datetime' => $at,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($purchase, $items, incoming: true);

            return $purchase->load(['supplier', 'items']);
        });
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $purchase->load('items.product');
            $this->reverseStock($purchase);

            $items = $this->normalizer->normalize($data['items'], useSalePrice: false);
            $this->applyPurchasePrices($items, $data['items']);
            $subtotal = round(collect($items)->sum('line_total'), 2);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? $purchase->document_datetime);

            $purchase->update([
                'supplier_id' => $data['supplier_id'] ?? $purchase->supplier_id,
                'document_datetime' => $at,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? $purchase->notes,
            ]);

            $purchase->items()->delete();
            $this->syncLines($purchase, $items, incoming: true);

            return $purchase->fresh(['supplier', 'items']);
        });
    }

    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase->load('items.product');
            $this->reverseStock($purchase);
            $purchase->delete();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $raw
     */
    private function applyPurchasePrices(array &$items, array $raw): void
    {
        foreach ($items as $index => &$item) {
            $unitCost = isset($raw[$index]['unit_cost'])
                ? (float) $raw[$index]['unit_cost']
                : (isset($raw[$index]['unit_price']) ? (float) $raw[$index]['unit_price'] : $item['unit_cost']);
            $item['unit_cost'] = round($unitCost, 2);
            $item['unit_price'] = $item['unit_cost'];
            $item['line_total'] = round($item['quantity'] * $item['unit_cost'], 2);
        }
        unset($item);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncLines(Purchase $purchase, array $items, bool $incoming): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];

            $purchase->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);

            if ($incoming) {
                $this->stock->applyIncomingCost($product, $item['quantity'], (float) $item['unit_cost']);
                $this->stock->increment($product, $item['quantity']);
            }
        }
    }

    private function reverseStock(Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->stock->decrement($item->product, $item->quantity);
        }
    }
}

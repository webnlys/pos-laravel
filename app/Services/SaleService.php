<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private TaxService $taxes,
        private StockService $stock,
        private DocumentItemNormalizer $normalizer,
    ) {}

    public function create(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? null);
            $totals = $this->taxes->totals($items, (float) ($data['discount'] ?? 0), $at);

            $sale = Sale::query()->create([
                'number' => $this->numbers->next('INV', Sale::class),
                'customer_id' => $data['customer_id'],
                'user_id' => $userId,
                'quotation_id' => $data['quotation_id'] ?? null,
                'document_datetime' => $at,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'status' => 'confirmed',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($sale, $items, $totals['taxes'], decrement: true);

            return $sale->load(['customer', 'items', 'taxes', 'payments']);
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $sale->load('items.product');
            $this->restoreStock($sale);

            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? $sale->document_datetime);
            $totals = $this->taxes->totals($items, (float) ($data['discount'] ?? 0), $at);

            $sale->update([
                'customer_id' => $data['customer_id'] ?? $sale->customer_id,
                'document_datetime' => $at,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? $sale->notes,
            ]);

            $sale->items()->delete();
            $sale->taxes()->delete();
            $this->syncLines($sale, $items, $totals['taxes'], decrement: true);

            return $sale->fresh(['customer', 'items', 'taxes', 'payments']);
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->load('items.product');
            $this->restoreStock($sale);
            $sale->delete();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array{name:string, rate_percent:float, amount:float}>  $taxes
     */
    private function syncLines(Sale $sale, array $items, array $taxes, bool $decrement): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];

            $sale->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'unit_cost' => $product->cost_price,
                'line_total' => $item['line_total'],
            ]);

            if ($decrement) {
                $this->stock->decrement($product, $item['quantity']);
            }
        }

        foreach ($taxes as $tax) {
            $sale->taxes()->create($tax);
        }
    }

    private function restoreStock(Sale $sale): void
    {
        foreach ($sale->items as $item) {
            if ($item->product) {
                $this->stock->increment($item->product, $item->quantity);
            }
        }
    }
}

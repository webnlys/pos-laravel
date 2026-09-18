<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private TaxService $taxes,
        private DocumentItemNormalizer $normalizer,
    ) {}

    public function create(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? null);
            $totals = $this->taxes->quotationTotals($items);

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
                'status' => $data['status'] ?? 'confirmed',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($sale, $totals['items'], $totals['taxes']);

            return $sale->load(['customer', 'items', 'taxes', 'payments']);
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? $sale->document_datetime);
            $totals = $this->taxes->quotationTotals($items);

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
            $this->syncLines($sale, $totals['items'], $totals['taxes']);

            return $sale->fresh(['customer', 'items', 'taxes', 'payments']);
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            if ($sale->quotation_id) {
                Quotation::query()->whereKey($sale->quotation_id)->update([
                    'status' => 'draft',
                    'converted_sale_id' => null,
                ]);
            }

            $sale->delete();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array{name:string, rate_percent:float, amount:float}>  $taxes
     */
    private function syncLines(Sale $sale, array $items, array $taxes): void
    {
        foreach ($items as $item) {
            $product = $item['product'];

            $sale->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_id' => $item['unit_id'] ?? null,
                'unit_name' => $item['unit_name'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'unit_cost' => $item['unit_cost'] ?? $product->cost_price,
                'discount' => $item['discount'] ?? 0,
                'tax_id' => $item['tax_id'] ?? null,
                'tax_name' => $item['tax_name'] ?? null,
                'tax_rate_percent' => $item['tax_rate_percent'] ?? null,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'line_total' => $item['line_total'],
            ]);
        }

        foreach ($taxes as $tax) {
            $sale->taxes()->create($tax);
        }
    }
}

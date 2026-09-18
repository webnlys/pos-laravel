<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private TaxService $taxes,
        private DocumentItemNormalizer $normalizer,
        private SaleService $sales,
    ) {}

    public function create(array $data, ?int $userId): Quotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? null);
            $totals = $this->taxes->quotationTotals($items);

            $quotation = Quotation::query()->create([
                'number' => $this->numbers->next('QT', Quotation::class),
                'customer_id' => $data['customer_id'],
                'user_id' => $userId,
                'document_datetime' => $at,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($quotation, $totals['items']);

            return $quotation->load(['customer', 'items', 'taxes']);
        });
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        if ($quotation->converted_sale_id) {
            throw ValidationException::withMessages([
                'quotation' => 'A converted quotation cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($quotation, $data) {
            $items = $this->normalizer->normalize($data['items']);
            $at = $this->normalizer->parseDatetime($data['document_datetime'] ?? $quotation->document_datetime);
            $totals = $this->taxes->quotationTotals($items);

            $quotation->update([
                'customer_id' => $data['customer_id'] ?? $quotation->customer_id,
                'document_datetime' => $at,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'status' => $data['status'] ?? $quotation->status,
                'notes' => $data['notes'] ?? $quotation->notes,
            ]);

            $quotation->items()->delete();
            $quotation->taxes()->delete();
            $this->syncLines($quotation, $totals['items']);

            return $quotation->fresh(['customer', 'items', 'taxes']);
        });
    }

    public function delete(Quotation $quotation): void
    {
        if ($quotation->converted_sale_id) {
            throw ValidationException::withMessages([
                'quotation' => 'A converted quotation cannot be deleted.',
            ]);
        }

        $quotation->delete();
    }

    public function convertToSale(Quotation $quotation, int $userId): Sale
    {
        if ($quotation->converted_sale_id) {
            throw ValidationException::withMessages([
                'quotation' => 'This quotation is already converted.',
            ]);
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->load('items');

            $sale = $this->sales->create([
                'customer_id' => $quotation->customer_id,
                'document_datetime' => now(),
                'discount' => (float) $quotation->discount,
                'notes' => $quotation->notes,
                'quotation_id' => $quotation->id,
                'items' => $quotation->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->all(),
            ], $userId);

            $quotation->update([
                'status' => 'converted',
                'converted_sale_id' => $sale->id,
            ]);

            return $sale;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncLines(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $quotation->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_id' => $item['unit_id'] ?? null,
                'unit_name' => $item['unit_name'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'tax_id' => $item['tax_id'] ?? null,
                'tax_name' => $item['tax_name'] ?? null,
                'tax_rate_percent' => $item['tax_rate_percent'] ?? null,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'line_total' => $item['line_total'],
            ]);
        }
    }
}

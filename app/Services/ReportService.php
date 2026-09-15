<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array{start:Carbon, end:Carbon}
     */
    public function dateRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : now()->startOfDay();
        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->endOfDay()
            : now()->endOfDay();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<string, mixed>
     */
    public function purchase(Request $request): array
    {
        $range = $this->dateRange($request);

        $query = PurchaseItem::query()
            ->with(['purchase.supplier', 'product'])
            ->whereHas('purchase', function ($q) use ($range, $request) {
                $q->whereBetween('document_datetime', [$range['start'], $range['end']]);
                if ($request->filled('supplier_id')) {
                    $q->where('supplier_id', $request->integer('supplier_id'));
                }
            });

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $items = $query->get();

        $rows = $items->map(function (PurchaseItem $item, int $index) {
            return [
                'sn' => $index + 1,
                'date' => $item->purchase?->document_datetime?->format('Y-m-d'),
                'supplier' => $item->purchase?->supplier?->name,
                'item' => $item->product_name,
                'qty' => $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'total' => (float) $item->line_total,
            ];
        })->all();

        return $this->wrap('Purchase Report', ['#', 'Date', 'Supplier', 'Item', 'Qty', 'Unit Cost', 'Total'], $rows, [
            'qty' => array_sum(array_column($rows, 'qty')),
            'total' => round(array_sum(array_column($rows, 'total')), 2),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function sales(Request $request): array
    {
        $range = $this->dateRange($request);

        $query = Sale::query()
            ->with(['customer', 'items', 'payments'])
            ->whereBetween('document_datetime', [$range['start'], $range['end']]);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        $sales = $query->orderBy('document_datetime')->get();

        $rows = [];
        $sn = 1;
        foreach ($sales as $sale) {
            $items = $sale->items;
            if ($request->filled('product_id')) {
                $items = $items->where('product_id', $request->integer('product_id'));
                if ($items->isEmpty()) {
                    continue;
                }
            }

            foreach ($items as $item) {
                $rows[] = [
                    'sn' => $sn++,
                    'date' => $sale->document_datetime->format('Y-m-d'),
                    'customer' => $sale->customer?->name,
                    'invoice' => $sale->number,
                    'item' => $item->product_name,
                    'qty' => $item->quantity,
                    'price' => (float) $item->unit_price,
                    'tax' => (float) $sale->tax_total,
                    'total' => (float) $item->line_total,
                    'paid' => $sale->paidAmount(),
                    'due' => $sale->dueAmount(),
                ];
            }
        }

        return $this->wrap('Sales Report', ['#', 'Date', 'Customer', 'Invoice', 'Item', 'Qty', 'Price', 'Tax', 'Total', 'Paid', 'Due'], $rows, [
            'qty' => array_sum(array_column($rows, 'qty')),
            'total' => round(array_sum(array_column($rows, 'total')), 2),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function invoiceProfitLoss(Request $request): array
    {
        $range = $this->dateRange($request);

        $sales = Sale::query()
            ->with('items')
            ->whereBetween('document_datetime', [$range['start'], $range['end']])
            ->orderBy('document_datetime')
            ->get();

        $rows = $sales->values()->map(function (Sale $sale, int $index) {
            $qty = (int) $sale->items->sum('quantity');
            $cogs = (float) $sale->items->sum(fn ($item) => $item->quantity * (float) $item->unit_cost);
            $netSales = (float) $sale->subtotal - (float) $sale->discount;
            $profit = round($netSales - $cogs, 2);

            return [
                'sn' => $index + 1,
                'invoice' => $sale->number,
                'date' => $sale->document_datetime->format('Y-m-d'),
                'qty' => $qty,
                'sale' => $netSales,
                'cost' => round($cogs, 2),
                'tax' => (float) $sale->tax_total,
                'profit' => $profit,
            ];
        })->all();

        return $this->wrap('Invoice Profit / Loss', ['#', 'Invoice', 'Date', 'Qty', 'Net Sale', 'Cost', 'Tax', 'Profit'], $rows, [
            'qty' => array_sum(array_column($rows, 'qty')),
            'sale' => round(array_sum(array_column($rows, 'sale')), 2),
            'cost' => round(array_sum(array_column($rows, 'cost')), 2),
            'tax' => round(array_sum(array_column($rows, 'tax')), 2),
            'profit' => round(array_sum(array_column($rows, 'profit')), 2),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function itemProfitLoss(Request $request): array
    {
        $range = $this->dateRange($request);

        $query = SaleItem::query()
            ->with('sale')
            ->whereHas('sale', fn ($q) => $q->whereBetween('document_datetime', [$range['start'], $range['end']]));

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $grouped = $query->get()->groupBy('product_id');

        $rows = [];
        $sn = 1;
        foreach ($grouped as $items) {
            /** @var Collection<int, SaleItem> $items */
            $first = $items->first();
            $qty = (int) $items->sum('quantity');
            $sale = (float) $items->sum('line_total');
            $cost = (float) $items->sum(fn (SaleItem $item) => $item->quantity * (float) $item->unit_cost);
            $tax = (float) $items->sum(fn (SaleItem $item) => $item->sale ? ((float) $item->sale->tax_total) : 0);

            $rows[] = [
                'sn' => $sn++,
                'item' => $first?->product_name,
                'qty' => $qty,
                'sale' => round($sale, 2),
                'cost' => round($cost, 2),
                'tax' => round($tax, 2),
                'profit' => round($sale - $cost, 2),
            ];
        }

        return $this->wrap('Item Profit / Loss', ['#', 'Item', 'Qty', 'Sale', 'Cost', 'Tax', 'Profit'], $rows, [
            'qty' => array_sum(array_column($rows, 'qty')),
            'sale' => round(array_sum(array_column($rows, 'sale')), 2),
            'cost' => round(array_sum(array_column($rows, 'cost')), 2),
            'profit' => round(array_sum(array_column($rows, 'profit')), 2),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function totalProfitLoss(Request $request): array
    {
        $range = $this->dateRange($request);

        $sales = Sale::query()
            ->with('items')
            ->whereBetween('document_datetime', [$range['start'], $range['end']])
            ->get()
            ->groupBy(fn (Sale $sale) => $sale->document_datetime->format('Y-m-d'));

        $rows = [];
        $sn = 1;
        foreach ($sales as $date => $daySales) {
            $netSales = (float) $daySales->sum(fn (Sale $sale) => (float) $sale->subtotal - (float) $sale->discount);
            $cogs = (float) $daySales->sum(fn (Sale $sale) => $sale->items->sum(fn ($item) => $item->quantity * (float) $item->unit_cost));

            $rows[] = [
                'sn' => $sn++,
                'date' => $date,
                'net_sales' => round($netSales, 2),
                'cogs' => round($cogs, 2),
                'profit' => round($netSales - $cogs, 2),
            ];
        }

        return $this->wrap('Total Profit / Loss', ['#', 'Date', 'Net Sales', 'COGS', 'Profit'], $rows, [
            'net_sales' => round(array_sum(array_column($rows, 'net_sales')), 2),
            'cogs' => round(array_sum(array_column($rows, 'cogs')), 2),
            'profit' => round(array_sum(array_column($rows, 'profit')), 2),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function productList(Request $request): array
    {
        $range = $this->dateRange($request);

        $query = Product::query()->whereBetween('created_at', [$range['start'], $range['end']]);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%");
            });
        }

        $rows = $query->orderBy('name')->get()->values()->map(function (Product $product, int $index) {
            return [
                'sn' => $index + 1,
                'name' => $product->name,
                'sku' => $product->sku,
                'status' => $product->is_active ? 'Active' : 'Inactive',
            ];
        })->all();

        return $this->wrap('Product List', ['#', 'Product Name', 'SKU', 'Status'], $rows, [
            'count' => count($rows),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function stockSummary(Request $request): array
    {
        $range = $this->dateRange($request);

        $in = PurchaseItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('purchase', fn ($q) => $q->whereBetween('document_datetime', [$range['start'], $range['end']]))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $out = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('sale', fn ($q) => $q->whereBetween('document_datetime', [$range['start'], $range['end']]))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $products = Product::query()->orderBy('name')->get();

        $rows = $products->values()->map(function (Product $product, int $index) use ($in, $out) {
            return [
                'sn' => $index + 1,
                'item' => $product->name,
                'sku' => $product->sku,
                'in' => (int) ($in[$product->id] ?? 0),
                'out' => (int) ($out[$product->id] ?? 0),
                'available' => (int) $product->stock_qty,
            ];
        })->all();

        return $this->wrap('Stock Summary', ['#', 'Item', 'SKU', 'In', 'Out', 'Available'], $rows, [
            'in' => array_sum(array_column($rows, 'in')),
            'out' => array_sum(array_column($rows, 'out')),
            'available' => array_sum(array_column($rows, 'available')),
        ], $range);
    }

    /**
     * @return array<string, mixed>
     */
    public function valuation(Request $request): array
    {
        $range = $this->dateRange($request);
        $products = Product::query()->orderBy('name')->get();

        $rows = $products->values()->map(function (Product $product, int $index) {
            $value = round(((int) $product->stock_qty) * ((float) $product->cost_price), 2);

            return [
                'sn' => $index + 1,
                'item' => $product->name,
                'sku' => $product->sku,
                'available' => (int) $product->stock_qty,
                'unit_cost' => (float) $product->cost_price,
                'asset_value' => $value,
            ];
        })->all();

        return $this->wrap('Inventory Valuation', ['#', 'Item', 'SKU', 'Available', 'Unit Cost', 'Asset Value'], $rows, [
            'asset_value' => round(array_sum(array_column($rows, 'asset_value')), 2),
        ], $range);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $totals
     * @param  array{start:Carbon, end:Carbon}  $range
     * @return array<string, mixed>
     */
    private function wrap(string $title, array $columns, array $rows, array $totals, array $range): array
    {
        return [
            'title' => $title,
            'start_date' => $range['start']->toDateString(),
            'end_date' => $range['end']->toDateString(),
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}

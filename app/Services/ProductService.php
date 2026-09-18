<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private UnitService $units) {}

    /**
     * @param  array<string, mixed>  $item
     */
    public function resolveFromLine(array $item): ?Product
    {
        $productId = $item['product_id'] ?? null;
        if ($productId) {
            $product = Product::query()->with('unit')->find($productId);
            if ($product) {
                return $product;
            }
        }

        $name = trim((string) ($item['product_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $existing = Product::query()
            ->with('unit')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $unit = $this->units->resolve(
            isset($item['unit_id']) ? (int) $item['unit_id'] : null,
            $item['unit_name'] ?? null,
        );

        return Product::query()->create([
            'name' => $name,
            'sku' => $this->uniqueSku($name),
            'unit_id' => $unit?->id,
            'sale_price' => round((float) ($item['unit_price'] ?? 0), 2),
            'cost_price' => round((float) ($item['unit_cost'] ?? 0), 2),
            'stock_qty' => 0,
            'is_active' => true,
        ]);
    }

    private function uniqueSku(string $name): string
    {
        $base = strtoupper(Str::slug($name, '-'));
        if ($base === '') {
            $base = 'PRD-'.strtoupper(Str::random(6));
        }

        $base = Str::limit($base, 80, '');
        $sku = $base;
        $i = 1;

        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.$i;
            $i++;
        }

        return $sku;
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit?->name,
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
            ] : null),
            'sale_price' => (float) $this->sale_price,
            'cost_price' => (float) $this->cost_price,
            'stock_qty' => (int) $this->stock_qty,
            'is_active' => (bool) $this->is_active,
        ];
    }
}

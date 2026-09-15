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
            'sale_price' => (float) $this->sale_price,
            'cost_price' => (float) $this->cost_price,
            'stock_qty' => (int) $this->stock_qty,
            'is_active' => (bool) $this->is_active,
        ];
    }
}

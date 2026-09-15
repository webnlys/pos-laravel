<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'document_datetime' => $this->document_datetime,
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items'),
        ];
    }
}

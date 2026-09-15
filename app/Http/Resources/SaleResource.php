<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'quotation_id' => $this->quotation_id,
            'document_datetime' => $this->document_datetime,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'paid' => $this->whenLoaded('payments', fn () => $this->paidAmount(), $this->paidAmount()),
            'due' => $this->dueAmount(),
            'items' => $this->whenLoaded('items'),
            'taxes' => $this->whenLoaded('taxes'),
        ];
    }
}

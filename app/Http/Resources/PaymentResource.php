<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sale_id' => $this->sale_id,
            'sale' => $this->whenLoaded('sale'),
            'kind' => $this->sale_id ? 'invoice' : 'advance',
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
        ];
    }
}

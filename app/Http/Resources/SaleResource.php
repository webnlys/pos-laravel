<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->paymentSummary();

        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'quotation_id' => $this->quotation_id,
            'document_datetime' => $this->document_datetime,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'overall_discount' => (float) $this->overall_discount,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'status' => $summary['status'],
            'notes' => $this->notes,
            'include_account_info' => (bool) $this->include_account_info,
            'delivery_required' => (bool) $this->delivery_required,
            'fixing_required' => (bool) $this->fixing_required,
            'paid' => $summary['paid'],
            'due' => $summary['due'],
            'advance' => $summary['advance'],
            'items' => $this->whenLoaded('items'),
            'taxes' => $this->whenLoaded('taxes'),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}

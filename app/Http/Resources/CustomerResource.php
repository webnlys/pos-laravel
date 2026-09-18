<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->accountSummary();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'charged' => $summary['charged'],
            'paid' => $summary['paid'],
            'due' => $summary['due'],
            'advance' => $summary['advance'],
            'created_at' => $this->created_at,
            'sales' => SaleResource::collection($this->whenLoaded('sales')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}

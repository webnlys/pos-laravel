<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $charged = (float) $this->sales()->sum('total');
        $paid = (float) $this->payments()->sum('amount');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'due' => round($charged - $paid, 2),
            'created_at' => $this->created_at,
        ];
    }
}

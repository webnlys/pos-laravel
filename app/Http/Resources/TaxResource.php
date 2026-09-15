<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rate_percent' => (float) $this->rate_percent,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
        ];
    }
}

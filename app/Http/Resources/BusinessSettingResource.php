<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? asset('storage/'.$this->logo) : null,
            'signature' => $this->signature ? asset('storage/'.$this->signature) : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'currency' => $this->currencyCode(),
            'tagline' => $this->tagline,
            'invoice_terms' => $this->invoice_terms,
            'quotation_terms' => $this->quotation_terms,
            'currencies' => config('currencies.codes'),
        ];
    }
}

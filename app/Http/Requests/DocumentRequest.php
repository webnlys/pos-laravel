<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->isCustomer()) {
            $this->merge(['customer_id' => $this->user()->customer_id]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'document_datetime' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($this->routeIs('admin.purchases.*')) {
            $rules['supplier_id'] = ['required', 'exists:suppliers,id'];
        } else {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        }

        return $rules;
    }
}

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
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = $item['product_id'] ?? null;
            $items[$index]['product_id'] = $productId ? (int) $productId : null;

            if (array_key_exists('product_name', $item)) {
                $items[$index]['product_name'] = trim((string) $item['product_name']);
            }
        }

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        $rules = [
            'document_datetime' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:items.*.product_name'],
            'items.*.product_name' => ['nullable', 'string', 'max:255', 'required_without:items.*.product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'exists:taxes,id'],
        ];

        if ($this->routeIs('admin.purchases.*')) {
            $rules['supplier_id'] = ['required', 'exists:suppliers,id'];
        } else {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        }

        return $rules;
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $unitId = $this->input('unit_id');
        $unitName = $this->input('unit_name');

        $this->merge([
            'unit_id' => $unitId ? (int) $unitId : null,
            'unit_name' => is_string($unitName) ? trim($unitName) : $unitName,
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $id = $product instanceof Product ? $product->id : $product;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'unit_name' => ['nullable', 'string', 'max:50'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

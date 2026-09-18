<?php

namespace App\Http\Requests\Admin;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'sale_id' => ['nullable', 'exists:sales,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,cheque,other'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $saleId = $this->input('sale_id');
            $customerId = $this->input('customer_id');

            if (! $saleId || ! $customerId) {
                return;
            }

            $sale = Sale::query()->find($saleId);
            if ($sale && (int) $sale->customer_id !== (int) $customerId) {
                $validator->errors()->add('sale_id', 'This invoice does not belong to the selected customer.');
            }
        });
    }
}

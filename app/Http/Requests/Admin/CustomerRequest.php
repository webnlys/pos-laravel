<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');
        $address = $this->input('address');

        $this->merge([
            'email' => is_string($email) && trim($email) === '' ? null : $email,
            'phone' => is_string($phone) && trim($phone) === '' ? null : $phone,
            'address' => is_string($address) && trim($address) === '' ? null : $address,
        ]);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $id = $customer instanceof Customer ? $customer->id : $customer;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($id)],
            'address' => ['nullable', 'string'],
        ];
    }
}

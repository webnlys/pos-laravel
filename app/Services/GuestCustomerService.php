<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Str;

class GuestCustomerService
{
    public function resolve(string $email, string $phone): Customer
    {
        $email = Str::lower(trim($email));
        $phone = trim($phone);

        $customer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($customer) {
            return $customer;
        }

        $customer = Customer::query()
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            return $customer;
        }

        $local = Str::before($email, '@');
        $name = trim(Str::title(str_replace(['.', '_', '-'], ' ', $local)));

        return Customer::query()->create([
            'name' => $name !== '' ? $name : 'Guest '.$phone,
            'email' => $email,
            'phone' => $phone,
        ]);
    }
}

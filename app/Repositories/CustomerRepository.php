<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends Repository
{
    protected function model(): string
    {
        return Customer::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone', 'email'];
    }
}

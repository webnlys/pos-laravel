<?php

namespace App\Repositories;

use App\Models\Supplier;

class SupplierRepository extends Repository
{
    protected function model(): string
    {
        return Supplier::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone', 'email'];
    }
}

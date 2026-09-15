<?php

namespace App\Repositories;

use App\Models\Tax;

class TaxRepository extends Repository
{
    protected function model(): string
    {
        return Tax::class;
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }
}

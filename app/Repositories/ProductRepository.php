<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends Repository
{
    protected function model(): string
    {
        return Product::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'sku'];
    }
}

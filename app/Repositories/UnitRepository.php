<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository extends Repository
{
    protected function model(): string
    {
        return Unit::class;
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }
}

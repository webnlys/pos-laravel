<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc', array $with = []): LengthAwarePaginator
    {
        $query = $this->filtered($request)
            ->withSum('sales', 'total')
            ->withSum('payments', 'amount');

        if ($with !== []) {
            $query->with($with);
        }

        return $this->paginateQuery($query, $request, $orderBy, $direction);
    }
}

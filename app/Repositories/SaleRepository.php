<?php

namespace App\Repositories;

use App\Models\Sale;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository extends Repository
{
    protected function model(): string
    {
        return Sale::class;
    }

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc', array $with = []): LengthAwarePaginator
    {
        $query = $this->query()->with(['customer', 'payments']);

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term) {
                $operator = Search::likeOperator($builder->getConnection());
                $builder->where('number', $operator, "%{$term}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', $operator, "%{$term}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        return $this->paginateQuery($query, $request, $orderBy, $direction);
    }
}

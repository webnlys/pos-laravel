<?php

namespace App\Repositories;

use App\Models\Purchase;
use App\Support\Search;
use Illuminate\Http\Request;

class PurchaseRepository extends Repository
{
    protected function model(): string
    {
        return Purchase::class;
    }

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc')
    {
        $query = $this->query()->with('supplier');

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term) {
                $operator = Search::likeOperator($builder->getConnection());
                $builder->where('number', $operator, "%{$term}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', $operator, "%{$term}%"));
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        return $query->orderBy($orderBy, $direction)->paginate($request->integer('per_page', 15));
    }
}

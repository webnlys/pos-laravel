<?php

namespace App\Repositories;

use App\Models\Quotation;
use App\Support\Search;
use Illuminate\Http\Request;

class QuotationRepository extends Repository
{
    protected function model(): string
    {
        return Quotation::class;
    }

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc')
    {
        $query = $this->query()->with('customer');

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

        return $query->orderBy($orderBy, $direction)->paginate($request->integer('per_page', 15));
    }
}

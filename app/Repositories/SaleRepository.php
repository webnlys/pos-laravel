<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleRepository extends Repository
{
    protected function model(): string
    {
        return Sale::class;
    }

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc')
    {
        $query = $this->query()->with(['customer', 'payments']);

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term) {
                $builder->where('number', 'ilike', "%{$term}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'ilike', "%{$term}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        return $query->orderBy($orderBy, $direction)->paginate($request->integer('per_page', 15));
    }
}

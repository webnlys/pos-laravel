<?php

namespace App\Repositories;

use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Repository
{
    abstract protected function model(): string;

    public function query(): Builder
    {
        /** @var class-string<Model> $class */
        $class = $this->model();

        return $class::query();
    }

    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc')
    {
        return $this->filtered($request)->orderBy($orderBy, $direction)->paginate($request->integer('per_page', 15));
    }

    public function filtered(Request $request): Builder
    {
        $query = $this->query();

        if ($request->filled('q') && $this->searchColumns() !== []) {
            $term = $request->string('q')->toString();
            $query->where(function (Builder $builder) use ($term) {
                $operator = Search::likeOperator($builder->getConnection());
                foreach ($this->searchColumns() as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, $operator, "%{$term}%");
                }
            });
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [];
    }
}

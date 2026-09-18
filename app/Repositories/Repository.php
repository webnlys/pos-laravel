<?php

namespace App\Repositories;

use App\Support\Pagination;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Repository
{
    abstract protected function model(): string;

    public function query(): Builder
    {
        /** @var class-string<Model> $class */
        $class = $this->model();

        return $class::query();
    }

    /**
     * @param  array<int, string>  $with
     */
    public function paginate(Request $request, string $orderBy = 'id', string $direction = 'desc', array $with = []): LengthAwarePaginator
    {
        $query = $this->filtered($request);

        if ($with !== []) {
            $query->with($with);
        }

        return $this->paginateQuery($query, $request, $orderBy, $direction);
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

    protected function paginateQuery(Builder $query, Request $request, string $orderBy, string $direction): LengthAwarePaginator
    {
        return $query
            ->orderBy($orderBy, $direction)
            ->paginate(Pagination::perPage($request), ['*'], 'page', Pagination::page($request))
            ->withQueryString();
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [];
    }
}

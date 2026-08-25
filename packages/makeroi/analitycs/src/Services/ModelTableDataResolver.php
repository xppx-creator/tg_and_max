<?php

namespace Makeroi\Analitics\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Makeroi\Analitics\Columns\Column;

class ModelTableDataResolver
{
    public function __construct(
        private readonly ModelTableQuery $query,
        private readonly ModelTableMapper $mapper,
    ) {}

    /**
     * @param  list<Column>  $columns
     * @return array{total: int, rows: list<array{id: int|string, cells: array<string, mixed>}>}
     */
    public function resolve(Builder $query, array $columns, Request $request, int $perPage): array
    {
        $query = $this->query->apply(clone $query, $columns, $request);

        $paginator = $query->paginate(
            perPage: $perPage,
            page: max((int) $request->integer('page', 1), 1),
        );

        $rows = [];

        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $rows[] = $this->mapper->mapRow($model, $columns);
            }
        }

        return [
            'total' => $paginator->total(),
            'rows' => $rows,
        ];
    }
}

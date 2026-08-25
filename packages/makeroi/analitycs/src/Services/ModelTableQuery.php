<?php

namespace Makeroi\Analitics\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Makeroi\Analitics\Columns\Column;
use packages\makeroi\analitycs\src\Columns\ColumnFilter;

class ModelTableQuery
{
    /**
     * @param  list<Column>  $columns
     */
    public function apply(Builder $query, array $columns, Request $request): Builder
    {
        $filters = $request->input('filters', []);

        if (is_array($filters)) {
            $this->applyFilters($query, $columns, $filters);
        }

        $this->applySort($query, $columns, $request);

        return $query;
    }

    /**
     * @param  list<Column>  $columns
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $columns, array $filters): void
    {
        foreach ($this->indexedFilters($columns) as $code => $filter) {
            if (! array_key_exists($code, $filters)) {
                continue;
            }

            $value = $filters[$code];

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $this->applyFilter($query, $filter, $value);
        }
    }

    private function applyFilter(Builder $query, ColumnFilter $filter, mixed $value): void
    {
        match ($filter->type) {
            'date-range' => $this->applyDateRangeFilter($query, $filter, $value),
            'string-contains' => $query->where($filter->attribute, 'like', '%'.$this->scalarString($value).'%'),
            'select' => $query->where($filter->attribute, $this->scalarString($value)),
            'multi-select' => $query->whereIn($filter->attribute, $this->scalarList($value)),
            default => null,
        };
    }

    private function applyDateRangeFilter(Builder $query, ColumnFilter $filter, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        $from = $this->parseDate($value['from'] ?? null);
        $to = $this->parseDate($value['to'] ?? null);

        if ($from !== null && $to !== null) {
            $query->whereBetween($filter->attribute, [$from, $to]);

            return;
        }

        if ($from !== null) {
            $query->where($filter->attribute, '>=', $from);
        }

        if ($to !== null) {
            $query->where($filter->attribute, '<=', $to);
        }
    }

    /**
     * @param  list<Column>  $columns
     */
    private function applySort(Builder $query, array $columns, Request $request): void
    {
        $sortColumn = $request->input('sort');

        if (! is_string($sortColumn) || $sortColumn === '') {
            return;
        }

        $column = collect($columns)->firstWhere('code', $sortColumn);

        if ($column === null || ! $column->sortable) {
            return;
        }

        $direction = strtolower((string) $request->input('sort_dir', 'desc'));

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($column->sortAttribute(), $direction);
    }

    /**
     * @param  list<Column>  $columns
     * @return array<string, ColumnFilter>
     */
    private function indexedFilters(array $columns): array
    {
        $indexed = [];

        foreach ($columns as $column) {
            foreach ($column->filters as $filter) {
                $indexed[$filter->code] = $filter;
            }
        }

        return $indexed;
    }

    private function scalarString(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * @return list<string>
     */
    private function scalarList(mixed $value): array
    {
        return array_values(array_filter(Arr::wrap($value), static fn ($item) => $item !== null && $item !== ''));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace Makeroi\Analitics\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Makeroi\Analitics\Columns\Column;

class ModelTableMapper
{
    /**
     * @param  list<Column>  $columns
     * @return array{id: int|string, cells: array<string, mixed>}
     */
    public function mapRow(Model $model, array $columns): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $cells[$column->code] = $this->resolveCell($model, $column);
        }

        return [
            'id' => $model->getKey(),
            'cells' => $cells,
        ];
    }

    private function resolveCell(Model $model, Column $column): mixed
    {
        if ($column->cell instanceof Closure) {
            return ($column->cell)($model);
        }

        $value = data_get($model, $column->attribute ?? $column->code);

        return match ($column->cell) {
            'timestamp' => $this->asTimestamp($value),
            default => $value,
        };
    }

    private function asTimestamp(mixed $value): ?int
    {
        if ($value instanceof CarbonInterface) {
            return $value->getTimestamp();
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        try {
            return Carbon::parse((string) $value)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }
}

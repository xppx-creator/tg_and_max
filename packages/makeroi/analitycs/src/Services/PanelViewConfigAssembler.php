<?php

namespace Makeroi\Analitics\Services;

use Makeroi\Analitics\Columns\Column;
use packages\makeroi\analitycs\src\Columns\ColumnFilter;

class PanelViewConfigAssembler
{
    /**
     * @param  list<Column>  $columns
     * @param  list<array<string, mixed>>  $bulkActions
     * @param  array<string, mixed>|null  $detail
     * @return array<string, mixed>
     */
    public function assemble(array $columns, array $bulkActions = [], ?array $detail = null): array
    {
        $config = [
            'bulkActions' => $bulkActions,
            'columns' => array_map(fn (Column $column) => $this->assembleColumn($column), $columns),
        ];

        if ($detail !== null) {
            $config['detail'] = $this->withModalSkeleton($detail);
        }

        return $config;
    }

    /**
     * Пока fetchDetail грузится, screen-engine подставляет row.cells в detail —
     * объектные ячейки мигают как JSON. Оборачиваем шаблон в modal-skeleton.
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function withModalSkeleton(array $detail): array
    {
        $template = $detail['template'] ?? null;
        if (! is_array($template)) {
            return $detail;
        }

        if (($template['type'] ?? null) === 'modal-skeleton') {
            return $detail;
        }

        $detail['template'] = [
            'type' => 'modal-skeleton',
            'props' => [
                'loading' => ['$bind' => 'loading'],
            ],
            'children' => [$template],
        ];

        return $detail;
    }

    /**
     * @return array<string, mixed>
     */
    private function assembleColumn(Column $column): array
    {
        $settings = $column->viewSettings;

        if ($column->viewType !== null) {
            $settings['type'] = $column->viewType;
        } elseif (! isset($settings['type'])) {
            $settings['type'] = 'text';
        }

        $assembled = [
            'title' => $column->title,
            'code' => $column->code,
            'settings' => $settings,
            'filters' => array_map(fn (ColumnFilter $filter) => $this->assembleFilter($filter), $column->filters),
            'sorts' => $this->assembleSorts($column),
        ];

        if ($column->width !== null) {
            $assembled['width'] = $column->width;
        }

        return $assembled;
    }

    /**
     * @return array<string, mixed>
     */
    private function assembleFilter(ColumnFilter $filter): array
    {
        $settings = match ($filter->type) {
            'date-range' => ['type' => 'date-range-filter'],
            'string-contains' => array_filter([
                'type' => 'string-contains-filter',
                'placeholder' => $filter->placeholder,
            ], static fn ($value) => $value !== null && $value !== ''),
            'select' => array_filter([
                'type' => 'select-filter',
                'placeholder' => $filter->placeholder,
                'options' => $filter->options,
            ], static fn ($value) => $value !== null && $value !== ''),
            'multi-select' => array_filter([
                'type' => 'multi-select-filter',
                'placeholder' => $filter->placeholder,
                'options' => $filter->options,
            ], static fn ($value) => $value !== null && $value !== ''),
            default => ['type' => str_ends_with($filter->type, '-filter') ? $filter->type : $filter->type.'-filter'],
        };

        return [
            'code' => $filter->code,
            'title' => $filter->title,
            'settings' => $settings,
        ];
    }

    /**
     * @return list<array{value: string, title: string}>
     */
    private function assembleSorts(Column $column): array
    {
        if (! $column->sortable) {
            return [];
        }

        $labels = [
            'asc' => 'А → Я',
            'desc' => 'Я → А',
        ];

        return array_map(
            fn (string $direction) => [
                'value' => $direction,
                'title' => $labels[$direction] ?? strtoupper($direction),
            ],
            $column->sortDirections,
        );
    }
}

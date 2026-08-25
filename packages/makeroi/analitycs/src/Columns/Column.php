<?php

namespace Makeroi\Analitics\Columns;

use Closure;use packages\makeroi\analitycs\src\Columns\ColumnFilter;

final class Column
{
    /**
     * @param  list<ColumnFilter>  $filters
     * @param  list<string>  $sortDirections
     * @param  Closure(mixed): mixed|string|null  $cell
     */
    public function __construct(
        public readonly string $code,
        public readonly string $title,
        public readonly ?string $attribute = null,
        public readonly bool $sortable = false,
        public readonly array $sortDirections = [],
        public readonly array $filters = [],
        public readonly Closure|string|null $cell = null,
        public readonly ?int $width = null,
        public readonly ?string $viewType = null,
        public readonly array $viewSettings = [],
    ) {}

    public static function make(string $code, string $title): self
    {
        return new self($code, $title, attribute: $code);
    }

    public function attribute(string $attribute): self
    {
        return $this->cloneWith(attribute: $attribute);
    }

    public function width(int $width): self
    {
        return $this->cloneWith(width: $width);
    }

    /**
     * @param  list<string>  $directions
     */
    public function sortable(array $directions = ['desc', 'asc']): self
    {
        return $this->cloneWith(sortable: true, sortDirections: $directions);
    }

    public function filter(ColumnFilter $filter): self
    {
        return $this->cloneWith(filters: [...$this->filters, $filter]);
    }

    public function filterDateRange(string $code, string $title, ?string $attribute = null): self
    {
        return $this->filter(ColumnFilter::dateRange($code, $title, $attribute ?? $this->attribute ?? $this->code));
    }

    public function filterStringContains(string $code, string $title, ?string $placeholder = null, ?string $attribute = null): self
    {
        return $this->filter(ColumnFilter::stringContains($code, $title, $attribute ?? $this->attribute ?? $this->code, $placeholder));
    }

    public function filterSelect(string $code, string $title, array $options, ?string $placeholder = null, ?string $attribute = null): self
    {
        return $this->filter(ColumnFilter::select($code, $title, $options, $attribute ?? $this->attribute ?? $this->code, $placeholder));
    }

    public function filterMultiSelect(string $code, string $title, array $options, ?string $placeholder = null, ?string $attribute = null): self
    {
        return $this->filter(ColumnFilter::multiSelect($code, $title, $options, $attribute ?? $this->attribute ?? $this->code, $placeholder));
    }

    public function asTimestamp(): self
    {
        return $this->cell('timestamp');
    }

    /**
     * Кнопка «Подробнее» → модалка detail (screen-engine action detail-modal).
     * Рендерер `detail-button` регистрируется в SPA пакета.
     */
    public function asDetailAction(string $buttonTitle = 'Подробнее'): self
    {
        return $this
            ->width($this->width ?? 118)
            ->viewType('template', [
                'template' => [
                    'type' => 'detail-button',
                    'props' => [
                        'text' => $buttonTitle,
                        'onClick' => ['action' => 'detail-modal'],
                    ],
                ],
            ])
            ->cell(static fn () => null);
    }

    public function cell(Closure|string $cell): self
    {
        return $this->cloneWith(cell: $cell);
    }

    public function viewType(string $type, array $settings = []): self
    {
        return $this->cloneWith(viewType: $type, viewSettings: $settings);
    }

    public function sortAttribute(): string
    {
        return $this->attribute ?? $this->code;
    }

    /**
     * @param  list<ColumnFilter>  $filters
     */
    private function cloneWith(
        ?string $attribute = null,
        ?bool $sortable = null,
        ?array $sortDirections = null,
        ?array $filters = null,
        Closure|string|null $cell = null,
        ?int $width = null,
        ?string $viewType = null,
        ?array $viewSettings = null,
    ): self {
        return new self(
            $this->code,
            $this->title,
            $attribute ?? $this->attribute,
            $sortable ?? $this->sortable,
            $sortDirections ?? $this->sortDirections,
            $filters ?? $this->filters,
            $cell ?? $this->cell,
            $width ?? $this->width,
            $viewType ?? $this->viewType,
            $viewSettings ?? $this->viewSettings,
        );
    }
}

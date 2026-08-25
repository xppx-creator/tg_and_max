<?php

namespace Makeroi\Analitics\Columns;

final class ColumnFilter
{
    /**
     * @param  list<array{value: string, title: string}>  $options
     */
    public function __construct(
        public readonly string $code,
        public readonly string $title,
        public readonly string $type,
        public readonly string $attribute,
        public readonly ?string $placeholder = null,
        public readonly array $options = [],
    ) {}

    public static function dateRange(string $code, string $title, string $attribute): self
    {
        return new self($code, $title, 'date-range', $attribute);
    }

    public static function stringContains(string $code, string $title, string $attribute, ?string $placeholder = null): self
    {
        return new self($code, $title, 'string-contains', $attribute, $placeholder);
    }

    /**
     * @param  list<array{value: string, title: string}>  $options
     */
    public static function select(string $code, string $title, array $options, string $attribute, ?string $placeholder = null): self
    {
        return new self($code, $title, 'select', $attribute, $placeholder, $options);
    }

    /**
     * @param  list<array{value: string, title: string}>  $options
     */
    public static function multiSelect(string $code, string $title, array $options, string $attribute, ?string $placeholder = null): self
    {
        return new self($code, $title, 'multi-select', $attribute, $placeholder, $options);
    }
}

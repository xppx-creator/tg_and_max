<?php

namespace Makeroi\Analitics\Detail;

/**
 * Fluent-конфиг модалки строки (section=config → detail).
 *
 * Контекст SPA: { row: cells, detail: payload, loading: bool }.
 */
final class DetailModal
{
    /**
     * @param  string|array<string, mixed>  $title
     * @param  array<string, mixed>|null  $template
     */
    public function __construct(
        private string|array $title = '',
        private ?string $width = null,
        private ?array $template = null,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  string|array<string, mixed>  $title
     */
    public function title(string|array $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function titleBind(string $path, mixed $default = null): self
    {
        return $this->title(DetailNode::bind($path, $default));
    }

    public function width(string $width): self
    {
        $clone = clone $this;
        $clone->width = $width;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $template
     */
    public function template(array $template): self
    {
        $clone = clone $this;
        $clone->template = $template;

        return $clone;
    }

    /**
     * @param  list<array<string, mixed>>  $children
     */
    public function stack(string $gap = '18px', array $children = []): self
    {
        return $this->template(DetailNode::vStack($gap, $children));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $detail = [
            'title' => $this->title,
        ];

        if ($this->width !== null && $this->width !== '') {
            $detail['width'] = $this->width;
        }

        if ($this->template !== null) {
            $detail['template'] = $this->template;
        }

        return $detail;
    }
}

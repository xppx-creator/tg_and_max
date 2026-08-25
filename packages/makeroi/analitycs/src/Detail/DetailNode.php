<?php

namespace Makeroi\Analitics\Detail;

/**
 * Узлы template-модалки (screen-engine TemplateNode).
 */
final class DetailNode
{
    /**
     * @return array{ $bind: string, $default?: mixed}
     */
    public static function bind(string $path, mixed $default = null): array
    {
        $node = ['$bind' => $path];

        if ($default !== null) {
            $node['$default'] = $default;
        }

        return $node;
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    public static function vStack(string $gap, array $children): array
    {
        return [
            'type' => 'v-stack',
            'props' => ['gap' => $gap],
            'children' => $children,
        ];
    }

    /**
     * @param  string|array<string, mixed>  $title
     * @param  string|array<string, mixed>  $description
     * @param  string|array<string, mixed>  $color
     * @return array<string, mixed>
     */
    public static function outcomeBanner(
        string|array $title,
        string|array $description,
        string|array $color,
    ): array {
        return [
            'type' => 'outcome-banner',
            'props' => [
                'title' => $title,
                'description' => $description,
                'color' => $color,
            ],
        ];
    }

    /**
     * @param  list<array{key: string, value: mixed}>  $items
     * @return array<string, mixed>
     */
    public static function keyValueList(
        string $title,
        array $items,
        string $labelWidth = '150px',
    ): array {
        return [
            'type' => 'key-value-list',
            'props' => [
                'title' => $title,
                'labelWidth' => $labelWidth,
                'items' => $items,
            ],
        ];
    }

    /**
     * @return array{key: string, value: mixed}
     */
    public static function kv(string $key, mixed $value): array
    {
        return [
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * @param  string|array<string, mixed>  $text
     * @param  string|array<string, mixed>  $color
     * @return array<string, mixed>
     */
    public static function badge(string|array $text, string|array $color): array
    {
        return [
            'type' => 'badge',
            'props' => [
                'text' => $text,
                'color' => $color,
            ],
        ];
    }

    /**
     * @param  string|array<string, mixed>  $value
     * @return array<string, mixed>
     */
    public static function date(string|array $value, string $format = 'DD.MM.YYYY HH:mm:ss'): array
    {
        return [
            'type' => 'date',
            'props' => [
                'value' => $value,
                'format' => $format,
            ],
        ];
    }

    /**
     * @param  string|array<string, mixed>  $text
     * @param  string|array<string, mixed>|null  $description
     * @return array<string, mixed>
     */
    public static function textPair(string|array $text, string|array|null $description = null): array
    {
        $props = ['text' => $text];

        if ($description !== null) {
            $props['description'] = $description;
        }

        return [
            'type' => 'text-pair',
            'props' => $props,
        ];
    }

    /**
     * @param  string|array<string, mixed>  $title
     * @param  string|array<string, mixed>|null  $href
     * @return array<string, mixed>
     */
    public static function anchor(
        string|array $title,
        string|array|null $href,
        string $target = '_blank',
    ): array {
        return [
            'type' => 'anchor',
            'props' => [
                'title' => $title,
                'href' => $href,
                'target' => $target,
            ],
        ];
    }

    /**
     * @param  string|array<string, mixed>  $items
     * @return array<string, mixed>
     */
    public static function actionLog(string $title, string|array $items): array
    {
        return [
            'type' => 'action-log',
            'props' => [
                'title' => $title,
                'items' => $items,
            ],
        ];
    }
}

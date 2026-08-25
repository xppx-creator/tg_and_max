<?php

namespace Makeroi\Analitics\Panels;

use Illuminate\Http\Request;
use Makeroi\Analitics\Concerns\InteractsWithPanelConfig;

/**
 * Базовая панель аналитики (аналог Screen в Orchid).
 *
 * Одна панель = свой API-маршрут + конфиг таблицы + источник данных + метрики.
 */
abstract class AnalyticsPanel
{
    use InteractsWithPanelConfig;

    /**
     * Уникальный идентификатор панели (сегмент URL).
     */
    abstract public static function slug(): string;

    /**
     * View-конфиг для SPA (section=config).
     *
     * @return array<string, mixed>
     */
    abstract public function config(): array;

    /**
     * Данные таблицы (section=data).
     *
     * @return array{total: int, rows: list<array{id: int|string, cells: array<string, mixed>}>}
     */
    abstract public function data(Request $request): array;

    public function name(): string
    {
        return static::slug();
    }

    /**
     * Человекочитаемый заголовок панели (каталог / SPA).
     */
    public function title(): string
    {
        return static::slug();
    }

    /**
     * Метрики шапки (section=metrics). Те же фильтры, что у data.
     *
     * @return list<array{label: string, value: int|float|string, format?: string}>
     */
    public function metrics(Request $request): array
    {
        return [];
    }

    /**
     * Полный payload section=metrics: KPI + опционально chart / series / top_scenarios.
     *
     * @return array{
     *     metrics: list<array{label: string, value: int|float|string, format?: string}>,
     *     chart?: list<array{key: string, label: string, value: int, color?: string}>,
     *     series?: list<array{date: string, label: string, success: int, error: int, in_progress?: int, total: int}>,
     *     top_scenarios?: list<array{name: string, total: int, success: int, error: int, in_progress?: int}>
     * }
     */
    public function dashboard(Request $request): array
    {
        return [
            'metrics' => $this->metrics($request),
        ];
    }

    /**
     * Payload модалки строки (section=detail&id=).
     * Контекст SPA: { row: cells, detail: этот массив }.
     *
     * @return array<string, mixed>
     */
    public function detailData(Request $request): array
    {
        return [];
    }

    public function enabled(): bool
    {
        return true;
    }

    public function routeUri(): string
    {
        return 'analytics/'.static::slug();
    }

    public function routeName(): string
    {
        return 'makeroi.analitycs.'.static::slug();
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return config('makeroi.analitycs.route.middleware', ['api']);
    }

    public function routePrefix(): string
    {
        return (string) config('makeroi.analitycs.route.prefix', 'api/v1');
    }

    /**
     * Относительный endpoint панели (без apiBase), например /analytics/table.
     */
    public function endpointPath(): string
    {
        $uri = '/'.ltrim($this->routeUri(), '/');

        return $uri;
    }

    /**
     * Фрагмент bootstrap для SPA.
     *
     * @return array{slug: string, title: string, endpoint: string}
     */
    public function toBootstrap(): array
    {
        return [
            'slug' => static::slug(),
            'title' => $this->title(),
            'endpoint' => $this->endpointPath(),
        ];
    }

    public function perPage(): int
    {
        return (int) config('makeroi.analitycs.default_per_page', 50);
    }

    public function perPageMax(): int
    {
        return (int) config('makeroi.analitycs.default_per_page_max', 200);
    }

    public function resolvePerPage(Request $request): int
    {
        return min(
            max((int) $request->integer('per_page', $this->perPage()), 1),
            $this->perPageMax(),
        );
    }
}

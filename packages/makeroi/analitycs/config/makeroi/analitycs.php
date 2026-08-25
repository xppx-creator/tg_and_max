<?php

return [

    'enabled' => env('MAKEROI_ANALITYCS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Панели аналитики (как Screen в Orchid)
    |--------------------------------------------------------------------------
    |
    | Список классов-наследников Makeroi\Analitics\Panels\AnalyticsPanel.
    | Каждая панель регистрирует GET {prefix}/{routeUri}?section=config|data|metrics.
    |
    */
    'panels' => [
        // App\Analytics\Panels\CopyLogsPanel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Панель по умолчанию (slug). null — первая из panels.
    |--------------------------------------------------------------------------
    */
    'default_panel' => env('MAKEROI_ANALITYCS_DEFAULT_PANEL'),

    /*
    |--------------------------------------------------------------------------
    | Общие настройки маршрутов API
    |--------------------------------------------------------------------------
    */
    'route' => [
        'prefix' => env('MAKEROI_ANALITYCS_ROUTE_PREFIX', 'api/v1'),
        'middleware' => array_filter(explode(',', env('MAKEROI_ANALITYCS_ROUTE_MIDDLEWARE', 'api'))),
    ],

    'default_per_page' => (int) env('MAKEROI_ANALITYCS_PER_PAGE', 50),
    'default_per_page_max' => (int) env('MAKEROI_ANALITYCS_PER_PAGE_MAX', 200),

    /*
    |--------------------------------------------------------------------------
    | SPA (сборка: npm run build → dist/)
    |--------------------------------------------------------------------------
    |
    | GET {route_prefix}/{path?} — статика из dist/ (index.html + assets).
    | При отдаче index.html в window.{window_key} инжектится bootstrap payload.
    |
    | spa.bootstrap_extra — array|callable(Request): array — доп. поля (token, …).
    */
    'spa' => [
        'enabled' => env('MAKEROI_ANALITYCS_SPA_ENABLED', true),
        'route_prefix' => env('MAKEROI_ANALITYCS_SPA_ROUTE_PREFIX', 'analytics'),
        'route_name' => env('MAKEROI_ANALITYCS_SPA_ROUTE_NAME', 'makeroi.analitycs.spa'),
        'middleware' => array_filter(explode(',', env('MAKEROI_ANALITYCS_SPA_MIDDLEWARE', 'web'))),
        'iframe_url' => env('MAKEROI_ANALITYCS_SPA_URL', '/analytics'),
        'window_key' => env('MAKEROI_ANALITYCS_WINDOW_KEY', '__MAKEROI_ANALITYCS__'),
        'bootstrap_extra' => null,
    ],

];

# makeroi/laravel-analitycs

Универсальный бэкенд **панелей аналитики** для виджетов makeROI: декларативные панели (как Screen в Orchid) + API `config` / `data` / `metrics` + SPA во фрейме.

**Composer:** `makeroi/laravel-analitycs` · **Namespace:** `Makeroi\Analitics\` · **Версия:** 0.3.0

---

## Идея

Пакет не знает домен виджета. Приложение регистрирует **N панелей** (базово одну) — классы-наследники `AnalyticsPanel` / `ModelAnalyticsPanel`. Каждая панель задаёт отображение (`columns` / `config`) и данные (`data`, `metrics`).

| Слой | Ответственность |
|------|-----------------|
| **Конфиг пакета** | `enabled`, `route.*`, `spa.*`, `default_panel`, `default_per_page*` |
| **Конфиг приложения** | `panels => [CopyLogsPanel::class, …]`, overrides |
| **Панель** | `slug`, `title()`, `config()`/`columns()`, `data()`, `metrics()` |
| **SPA** | читает bootstrap из `window.*` в `index.html`, ходит в API |

---

## HTTP API

На каждую панель:

```http
GET /{route.prefix}/{panel.routeUri}?section=config
GET /{route.prefix}/{panel.routeUri}?section=data&page=1&per_page=50&filters[code]=…
GET /{route.prefix}/{panel.routeUri}?section=metrics&filters[code]=…
```

Пример copy-leads: `/api/v1/analytics/table?section=config|data|metrics`.

Ответ пакета — **сырой JSON**. Обертку `{ success, data }` даёт middleware приложения (response-kit), если настроена.

### Metrics

```json
{
  "metrics": [
    { "label": "Всего", "value": 1284 },
    { "label": "Успешно", "value": 1200 }
  ]
}
```

---

## Установка

```bash
composer require makeroi/laravel-analitycs
php artisan vendor:publish --tag=makeroi-analitycs-config
```

Path-репозиторий:

```json
{
  "repositories": [{
    "type": "path",
    "url": "packages/makeroi/analitycs",
    "options": { "symlink": true }
  }],
  "require": { "makeroi/laravel-analitycs": "@dev" }
}
```

---

## Конфигурация

`config/makeroi/analitycs.php`:

| Ключ | Описание |
|------|----------|
| `enabled` | Включить маршруты |
| `panels` | Список классов панелей |
| `default_panel` | Slug панели по умолчанию (`null` → первая) |
| `route.prefix` / `route.middleware` | API |
| `spa.*` | Префикс SPA, `window_key`, `bootstrap_extra` (array\|callable) |
| `default_per_page` / `default_per_page_max` | Пагинация |

---

## Панель приложения

```php
use Makeroi\Analitics\Columns\Column;
use packages\makeroi\analitycs\src\Panels\ModelAnalyticsPanel;

final class CopyLogsPanel extends ModelAnalyticsPanel
{
    public static function slug(): string { return 'table'; }

    public function title(): string { return 'Лог копирований'; }

    public function model(): string { return CopyRun::class; }

    public function columns(): array
    {
        return [
            Column::make('when', 'Когда')
                ->attribute('started_at')
                ->sortable()
                ->filterDateRange('when_range', 'Период')
                ->asTimestamp()
                ->viewType('date', ['format' => 'DD.MM.YYYY HH:mm']),
        ];
    }

    public function metrics(Request $request): array
    {
        return [
            ['label' => 'Всего', 'value' => $this->query()->count()],
        ];
    }
}
```

Регистрация:

```php
'panels' => [
    \App\Analytics\Panels\CopyLogsPanel::class,
],
```

---

## Архитектура SPA

SPA — **часть Composer-пакета** (не отдельный npm publish и не submodule на `frontend-core/analitics`).

```
composer require makeroi/laravel-analitycs
        │
        ▼
  PHP API + SpaController ──serve──► dist/  (собранный Vite)
        ▲                               ▲
        │ inject bootstrap              │ npm run build
        │                               │
 window.__MAKEROI_ANALITYCS__ ◄── resources/spa/  (@makeroi/screen-engine)
```

| Вариант | Когда |
|---------|--------|
| **SPA внутри пакета** (канон) | `composer require` сразу отдаёт `/analytics`; один релиз PHP+UI |
| Отдельный FE-репо | только если нужен другой хост (MiniApp iframe). `frontend-core/analitics` — демо MiniApp, **не** источник dist пакета |

Сборка (нужен доступ к GitLab npm group 95, см. `.npmrc`):

```bash
cd packages/makeroi/analitycs
pnpm install
pnpm run build   # → dist/ (коммитить в релиз пакета)
```

---

## SPA

Исходники: `resources/spa/` (Vue 3 + Vite + `@makeroi/screen-engine`). Сборка → `dist/`.

| URL | Назначение |
|-----|------------|
| `GET /analytics` | `index.html` + **inject** `window.{window_key}` |
| `GET /analytics/assets/*` | JS/CSS |

Bootstrap (пример):

```json
{
  "apiBase": "/api/v1",
  "spaBase": "/analytics",
  "defaultPanel": "table",
  "panels": [{ "slug": "table", "title": "Лог копирований", "endpoint": "/analytics/table" }],
  "tableEndpoint": "/analytics/table",
  "token": null,
  "locale": "ru",
  "theme": null
}
```

Доп. поля (токены и т.д.): `spa.bootstrap_extra` — массив или `fn (Request $r): array`.

---

## Несколько панелей

Добавьте второй класс в `panels` — маршрут и пункт в SPA появятся сами. Дублирующий `slug` падает при boot.

---

## Тесты

```bash
cd packages/makeroi/analitycs && composer test
```

---

## См. также

- [`docs/CONFIG.md`](docs/CONFIG.md) — source vs view, Column DSL
- Host copy-leads: `docs/ANALITYCS.md`

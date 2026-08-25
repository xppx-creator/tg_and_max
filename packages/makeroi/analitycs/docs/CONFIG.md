# Конфигурация панели аналитики

## Слои

Пакет разделяет **что описывает виджет** и **что потребляет SPA**.

```
┌──────────────────────────────────────────────────────────────┐
│  Источник (source)                                           │
│  • panel.source.json в репозитории приложения                │
│  • PHP-массив / PanelDefinition в коде                       │
│  • настройки панели в БД (будущее)                           │
│                                                              │
│  Содержит: code, title, type, filter, sort — без UI-деревьев │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│  TablePanelConfigBuilder (конфигуратор)                      │
│  • маппинг type → settings.type, template, filters, sorts    │
│  • сборка detail из layout + пресетов                        │
│  • bulkActions, метрики шапки (v2)                           │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│  View config (output) — контракт SPA                         │
│  • bulkActions, columns[], detail                            │
│  • отдаётся в GET …?section=config                           │
│                                                              │
│  Эталон: resources/stubs/table.conf.json                     │
└──────────────────────────────────────────────────────────────┘
```

## Файлы-эталоны в пакете

| Путь | Назначение |
|------|------------|
| `resources/stubs/panel.source.example.json` | **Вход** — пример адекватных данных для конфигуратора |
| `resources/stubs/table.conf.json` | **Выход** — пример результата сборки (как у соседнего виджета) |

`table.conf.json` **не редактируют как основной конфиг**. Его либо генерирует builder из source, либо один раз собирают и кладут в `config/analytics/*.conf.json` приложения (временный путь в copy-leads).

## ModelAnalyticsPanel — декларативные колонки + Eloquent

Для типичной таблицы наследуйте `ModelAnalyticsPanel` вместо ручной реализации `data()`:

```php
use Makeroi\Analitics\Columns\Column;
use packages\makeroi\analitycs\src\Panels\ModelAnalyticsPanel;

final class CopyLogsPanel extends ModelAnalyticsPanel
{
    public static function slug(): string
    {
        return 'copy-logs';
    }

    public function model(): string
    {
        return CopyLog::class;
    }

    public function columns(): array
    {
        return [
            Column::make('when', 'Когда')
                ->attribute('started_at')
                ->sortable()
                ->filterDateRange('when_range', 'Период')
                ->asTimestamp()
                ->viewType('date', ['format' => 'DD.MM.YYYY HH:mm']),
            Column::make('lead', 'Сделка')
                ->attribute('lead_title')
                ->filterStringContains('lead_str', 'Сделка', 'Название сделки')
                ->cell(fn (CopyLog $log) => [
                    'title' => $log->lead_title,
                    'href' => $log->lead_url,
                ])
                ->viewType('anchor', ['target' => '_blank']),
        ];
    }

    public function query(): Builder
    {
        return parent::query()->where('account_id', auth()->id());
    }
}
```

Пакет сам:

- собирает `section=config` из `columns()` (`PanelViewConfigAssembler`);
- применяет `filters`, `sort`, `sort_dir`, `page`, `per_page` к Eloquent-запросу;
- маппит модели в `{ id, cells }`.

### Query-параметры `section=data`

| Параметр | Пример | Назначение |
|----------|--------|------------|
| `filters[{code}]` | `filters[lead_str]=ООО` | string-contains / select |
| `filters[{code}][]` | `filters[result_sel][]=sent` | multi-select |
| `filters[{code}][from]` / `[to]` | ISO-даты | date-range |
| `sort` | `when` | code колонки |
| `sort_dir` | `desc` | `asc` / `desc` |
| `page`, `per_page` | `1`, `50` | пагинация |

### Query-параметры `section=detail`

| Параметр | Пример | Назначение |
|----------|--------|------------|
| `id` | `42` | id строки таблицы |

Ответ: `{ detail: { … } }` — поля для `$bind: "detail.*"` в `config.detail.template`.
Колонка-триггер: `Column::make('details', '')->asDetailAction()`.

Для сложных ячеек — `->cell(fn (Model $m) => …)` или пресет `->asTimestamp()`.

`AnalyticsPanel` без модели остаётся для кастомных источников (stub JSON, внешние API).

## Панели (как Screen в Orchid)

В `config/makeroi/analitycs.php` задаётся список классов:

```php
'panels' => [
    \App\Analytics\Panels\TablePanel::class,
    \App\Analytics\Panels\CopyLogsPanel::class,
],
```

Каждый класс наследует `Makeroi\Analitics\Panels\AnalyticsPanel` и реализует:

| Метод | Назначение |
|-------|------------|
| `slug()` | сегмент URL (`analytics/{slug}`) |
| `title()` | заголовок панели (SPA / bootstrap) |
| `config()` | view-конфиг для `?section=config` |
| `data(Request)` | строки таблицы для `?section=data` |
| `metrics(Request)` | метрики шапки для `?section=metrics` |

Переопределяемые хуки: `routeUri()`, `routeName()`, `middleware()`, `name()`, `enabled()`, `toBootstrap()`.

Хелперы в трейте `InteractsWithPanelConfig`: `configFromFile()`, `configFromSource()`.

Для типичных таблиц наследуйте `ModelAnalyticsPanel` (`model()`, `columns()`, опционально `query()` / `metrics()`).

Канон регистрации — только `panels` + `default_panel`. Legacy-ключи `table.config_file` / `data_file` в host-конфиге могут оставаться мёртвым хвостом; runtime пакета 0.2 на них не опирается.

## Контракт builder'а

```php
use packages\makeroi\analitycs\src\Contracts\TablePanelConfigBuilder;

final class NotificationsPanelConfigBuilder implements TablePanelConfigBuilder
{
    public function build(array $source): array
    {
        // $source — декодированный panel.source.json или PanelDefinition::toArray()
        // return — структура как в table.conf.json
    }
}
```

Регистрация в `AppServiceProvider`:

```php
$this->app->bind(TablePanelConfigBuilder::class, NotificationsPanelConfigBuilder::class);
```

Пока builder в приложении не реализован — используйте `table.config_file` с заранее собранным view (как сейчас в copy-leads).

## Семантические типы колонок (source → view)

| `type` в source | Во view (`settings.type`) |
|-----------------|---------------------------|
| `date` | `date` + `format` |
| `lead_link` | `anchor` |
| `text` | `text` |
| `longtext` | `longtext` |
| `status` | `status` + `options[]` |
| `channel_bot_chat` | `template` (badge + text-pair) |
| `detail_action` | `template` (icon-button → detail-modal) |

Детализация (`detail`) в source задаётся через `layout` — список секций; builder разворачивает в `detail.template` (v-stack, key-value-list, …).

## Данные таблицы (отдельно от конфига панели)

`section=data` — другой контракт: `{ total, rows[].cells }`. См. `docs/template_table_data.json`.

Ключи в `cells` **обязаны совпадать** с `columns[].code` в view-конфиге.

## copy-leads (текущее состояние)

| Слой | Файл |
|------|------|
| View (временно, без builder) | `config/analytics/table.conf.json` |
| Data stub | `config/analytics/table.data.json` |
| Source (будущее) | `config/analytics/copy-logs.source.json` — по образцу `panel.source.example.json` |

Следующий шаг: `CopyLogsPanelConfigBuilder` + source с полями ТЗ §6 (`when`, `scenario`, `source_lead`, …).

# Changelog

## 0.3.20 - 2026-08-03

- Metrics: топ сценариев — tooltip по оси Y (`axis: 'y'`), без залипания на самом длинном баре.

## 0.3.19 - 2026-08-03

- Detail: fluent-конструкторы `DetailModal` / `DetailNode` вместо сырых массивов.
- SPA MetricsBar: заголовок из `panel.title` (bootstrap), без хардкода.
- `ModelAnalyticsPanel::detail()` принимает `DetailModal|array|null`.

## 0.3.18 - 2026-08-03

- Detail modal: общий `modal-skeleton` при `loading` — без мигания JSON по объектным ячейкам строки.
- `PanelViewConfigAssembler` автоматически оборачивает `detail.template`.

## 0.3.17 - 2026-08-03

- Metrics: графики на Chart.js (stacked bar по дням + горизонтальный топ сценариев).
- Таблица: колонки `minmax(px, 1fr)` — по умолчанию на всю ширину.
- LocalStore key v8 (сброс старых ширин).

## 0.3.16 - 2026-08-03

- Metrics: заголовок слева «Аналитика по копированию», KPI справа.
- date-filter / date-range-filter: автозакрытие календаря после выбора даты / интервала.

## 0.3.15 - 2026-08-03

- Фильтры колонок: алиасы `string-contains-filter` / `multi-select-filter` → рендереры screen-engine (пустой блок «Фильтр»).
- i18n: Cancel/Apply → Отмена/Применить.

## 0.3.14 - 2026-08-03

- Metrics: блоки «Результаты по дням» и «Топ сценариев» одинаковой высоты.

## 0.3.13 - 2026-08-03

- Таблица: колонки в px, ширина по содержимому — горизонтальный скролл, если шире экрана.

## 0.3.12 - 2026-08-03

- Metrics: `series` (результаты по дням), топ сценариев стек-барами; при скролле таблицы метрики сворачиваются (KPI + мини-полоска, кнопка «Графики»).

## 0.3.11 - 2026-08-03

- Detail modal: верх закреплён (`margin-top: 0`), контент скроллится внутри — не прыгает при развороте лога.
- `outcome-banner`: блок исхода без сворачивания (вместо expandable-text).

## 0.3.10 - 2026-08-03

- Detail: renderer `action-log` — компактный лог (время + текст в строку, expand только при контексте, заголовок с разделителем).

## 0.3.9 - 2026-08-03

- `asDetailAction()` → текстовая кнопка `detail-button` («Подробнее») вместо крошечного icon-button.
- SPA: клик по строке таблицы открывает detail (`table.openDetail`), интерактивы (ссылки/кнопки) не перехватываются.

## 0.3.8 - 2026-08-03

- section=metrics → `dashboard()`: KPI + `chart` + `top_scenarios` (опционально).
- SPA MetricsBar: stacked bar результатов и топ сценариев (успех/ошибки).
- Detail modal: `expandable-text` / `expandable` / `key-value-list` (как в stub), без фейкового «Нет записей лога».

## 0.3.7 - 2026-08-03

- SPA: token из bootstrap кладётся в `sessionStorage` (после strip из URL refresh не теряет auth).
- SPA: ошибки `fetchConfig`/`fetchData` больше не оставляют вечный skeleton — явный fatal вместо loader.
- screen-engine `defineTable` не ловит reject у `fetchConfig`; обход на стороне пакета.

## 0.3.6 - 2026-08-03

- API: `section=detail&id=` → `AnalyticsPanel::detailData()` / `ModelAnalyticsPanel::detailPayload()`.
- SPA: `fetchDetail` для модалки строки; кнопка «Настройки» скрыта (resize ширин через storage остаётся).
- SPA: iframe shell — `100vh`, sticky header таблицы, пагинация nav слева / sizer справа.
- Column: `asDetailAction()` → icon-button `detail-modal`.

## 0.3.5 - 2026-08-03

- SPA: убран ложный H-scroll от bulk-actions overlay (`inset-left: 51px` при пустых actions).
- SPA: колонки через `minmax(0, Nfr)` / фикс для узких; компактные метрики в ряд с «Настройки».
- Панель copy-leads: короче даты (`DD.MM HH:mm`), ссылки сделок `#id`, плотнее ширины.

## 0.3.4 - 2026-08-03

- SPA: стартовый `loader` заменён на skeleton (`PanelSkeletonLoader`).
- SPA: регистрация vue-renderers без `init` — в production нельзя перезаписать уже зарегистрированный `loader`.
- SPA: визуал таблицы — карточка, full-width rows, header фон, hover, shimmer вместо спиннера dataLoading.

## 0.3.3 - 2026-08-03

- SPA: корректный MIME/CORS для assets (CSS как text/css), снятие crossorigin у Vite HTML.
- SPA: тема light + locale ru по умолчанию; await GlobalLocalization до mount.
- SPA: full-width layout; метрики grid; default column width minmax(140px, 1fr).
- SPA: glob конфигов `../../../node_modules/@makeroi/**/config`.
## 0.3.2 - 2026-08-03

- SPA: `?panel=` в query выбирает активную панель; после bootstrap `token` убирается из URL (`history.replaceState`).

## 0.3.1 - 2026-07-31

- SPA: шапка metrics (`section=metrics`) через `extraLayouts` + `MetricsBar`.
- SPA: прокидывание `bootstrap.query` (в т.ч. `scenario_id` → `filters.scenario_sel`) в config/data/metrics.

## 0.3.0 - 2026-07-31

- SPA на `@makeroi/screen-engine` (+ vue-renderers) из GitLab npm registry.
- Источник SPA остаётся внутри Composer-пакета: `resources/spa/` → `npm run build` → `dist/`.
- Bootstrap `window.__MAKEROI_ANALITYCS__` → `PanelApi` → `defineTable` (без MiniApp/iframe).
- `frontend-core/analitics` — отдельный MiniApp-демо; не источник dist для Laravel-пакета.

## 0.2.0 - 2026-07-30

- N панелей через `config.panels` + `PanelRegistry` (продуктовый канон, не roadmap).
- `AnalyticsPanel::title()`, `metrics(Request)`, `toBootstrap()`; API `section=metrics`.
- `default_panel` в конфиге; канон без legacy `table.config_file` / `data_file` в defaults пакета.
- `AnalyticsBootstrap` + inject `window.{spa.window_key}` в SPA `index.html` (пути, панели, token, locale).
- `spa.bootstrap_extra` (array|callable) для токенов/доп. полей приложения.
- Vue 3 SPA в `resources/spa/` (metrics + таблица + фильтры/сортировка/пагинация); stub UI удалён.
- Filter types в view-конфиге: `string-contains-filter`, `date-range-filter`, `select-filter`, `multi-select-filter`.

## 0.1.0 - 2026-06-25

- Первый релиз: API `GET analytics/table` с `section=config|data`, конфиг через `config/makeroi/analitycs.php` и ENV.
- Namespace `Makeroi\Analitics\`, панели через `AnalyticsPanel` / `ModelAnalyticsPanel`, `Column`, `TablePanelConfigBuilder`.
- Source vs view: `panel.source.example.json` (вход), `table.conf.json` (выход для SPA), `docs/CONFIG.md`.
- SPA в `resources/spa/` (Vite), сборка в `dist/`, маршрут `GET /analytics/{path?}`; заглушка отображает конфиг панели.

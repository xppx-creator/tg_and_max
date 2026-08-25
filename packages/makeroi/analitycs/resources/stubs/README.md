# Stubs — форматы конфигурации панели

## Два уровня

| Файл | Роль | Кто пишет |
|------|------|-----------|
| **`panel.source.example.json`** | **Вход** конфигуратора — доменные поля, типы колонок, фильтры без UI-шаблонов | разработчик виджета / панель в PHP |
| **`table.conf.json`** | **Выход** — готовый контракт для SPA (`columns`, `filters`, `sorts`, `detail.template`, …) | `TablePanelConfigBuilder` или ручная сборка |

```
panel.source (адекватные входные данные)
        │
        ▼
 TablePanelConfigBuilder   ← PHP: типы колонок → шаблоны, detail, bulkActions
        │
        ▼
 table.conf.json (section=config)  →  SPA рисует таблицу
```

**`table.conf.json` в этой папке — не то, что правят руками в проде.** Это эталон **результата** сборки (скопирован с рабочего виджета). В copy-leads пока отдаётся напрямую через `MAKEROI_ANALITYCS_CONFIG_FILE` до появления своего builder'а.

## Что на входе (source)

Короткое описание панели: код колонки, заголовок, семантический тип, нужны ли фильтр/сортировка. Без `$bind`, `template`-деревьев и прочей разметки SPA.

См. `panel.source.example.json`.

## Что на выходе (view / table.conf)

Полный JSON для фронта. Эталон — `table.conf.json` и `docs/template_table_conf.json` в хост-приложении.

См. [`docs/CONFIG.md`](../../docs/CONFIG.md).

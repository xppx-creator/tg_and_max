import './assets/base.scss';

import { defineScreen, layout, registerTemplateFormatter, withRenderer } from '@makeroi/screen-engine';
import type { LayoutNode } from '@makeroi/screen-engine';
import { defineTable } from '@makeroi/screen-engine/vue-renderers';
import screenEngineVueRenderers from '@makeroi/screen-engine/vue-renderers/options';
import { dateFormatter } from '@makeroi/screen-engine/vue-renderers/Utils/DateFormatter';
import '@makeroi/components/init';
import { withConfig } from '@makeroi/app-config';
import { GlobalLocalization, withGlobalLocals } from '@makeroi/i18n/GlobalLocalization';
import appConfigs from 'virtual:@makeroi/app-config/configs';

import {
  persistBootstrapToken,
  readBootstrap,
  resolveActivePanel,
  stripTokenFromUrl,
} from './bootstrap';
import { createPanelApi, initialTableParams } from './api/PanelApi';
import { getLocalStore } from './storage/LocalStore';
import { setDashboard, setPanelTitle } from './metricsState';
import MetricsBar from './components/MetricsBar.vue';
import PanelSkeletonLoader from './components/PanelSkeletonLoader.vue';
import ModalSkeletonLoader from './components/ModalSkeletonLoader.vue';
import DetailButton from './components/DetailButton.vue';
import ActionLog from './components/ActionLog.vue';
import OutcomeBanner from './components/OutcomeBanner.vue';
import AutoCloseDateFilter from './components/AutoCloseDateFilter.vue';
import { defineComponent, h } from 'vue';

function dateFilterRenderer(mode: 'single' | 'range') {
  return defineComponent({
    name: mode === 'range' ? 'AnalyticsDateRangeFilter' : 'AnalyticsDateFilter',
    props: {
      filter: { type: Object, default: undefined },
      value: { default: undefined },
      el: { default: undefined },
    },
    emits: ['update:value', 'update:el'],
    setup(props, { emit }) {
      return () =>
        h(AutoCloseDateFilter, {
          filter: props.filter as never,
          value: props.value,
          el: props.el,
          mode,
          'onUpdate:value': (value: unknown) => emit('update:value', value),
          'onUpdate:el': (el: unknown) => emit('update:el', el),
        });
    },
  });
}

const AnalyticsDateFilter = dateFilterRenderer('single');
const AnalyticsDateRangeFilter = dateFilterRenderer('range');

withConfig(appConfigs);
withConfig(import.meta.glob('./**/config/**/*.ts', { eager: true, import: 'default' }));

withGlobalLocals({
  ru: {
    Settings: 'Настройки',
    Show: 'Раскрыть',
    Hide: 'Скрыть',
    Cancel: 'Отмена',
    Apply: 'Применить',
    Default: 'По умолчанию',
  },
  en: {
    Settings: 'Settings',
    Show: 'Show',
    Hide: 'Hide',
    Cancel: 'Cancel',
    Apply: 'Apply',
    Default: 'Default',
  },
});

// В production withRenderer не даёт перезаписать уже зарегистрированный type —
// поэтому не подключаем vue-renderers/init, а регистрируем сами с нашим loader.
// screen-engine: string-contains / multi-select; конфиг панели шлёт *-filter.
withRenderer({
  ...screenEngineVueRenderers,
  'string-contains-filter': screenEngineVueRenderers['string-contains'],
  'multi-select-filter': screenEngineVueRenderers['multi-select'],
  'date-filter': AnalyticsDateFilter,
  'date-range-filter': AnalyticsDateRangeFilter,
  loader: PanelSkeletonLoader,
  'modal-skeleton': ModalSkeletonLoader,
  'analytics-metrics': MetricsBar,
  'detail-button': DetailButton,
  'action-log': ActionLog,
  'outcome-banner': OutcomeBanner,
});
registerTemplateFormatter('date', dateFormatter);

const bootstrap = persistBootstrapToken(readBootstrap());
stripTokenFromUrl();

const locale = String(bootstrap.locale || 'ru');
withConfig({ 'localization.current_locale': locale }, true);
document.documentElement.lang = locale;

const theme = String(bootstrap.theme || 'light');
document.documentElement.setAttribute('data-color-scheme', theme);

const { endpoint, panel } = resolveActivePanel(bootstrap);
setPanelTitle(panel?.title ? String(panel.title) : '');

function showFatal(message: string): void {
  const app = document.getElementById('app');
  if (!app) return;
  app.innerHTML =
    `<pre style="font:13px/1.4 ui-monospace,monospace;padding:16px;color:#b00;white-space:pre-wrap">${message}</pre>`;
}

function emptyConfig() {
  return { columns: [], bulkActions: [], total: 0 };
}

function emptyData() {
  return { rows: [], total: 0 };
}

async function boot(): Promise<void> {
  await GlobalLocalization.load();

  if (!endpoint) {
    showFatal('Панель аналитики не настроена (нет panels / tableEndpoint в bootstrap).');
    return;
  }

  if (!bootstrap.token) {
    showFatal(
      'Нет токена авторизации.\n' +
        'Откройте аналитику из виджета или добавьте ?token=… в URL.\n' +
        '(После первого открытия token убирается из адресной строки — обновите страницу только с тем же origin/вкладкой.)',
    );
    return;
  }

  const api = createPanelApi(endpoint, bootstrap);
  document.title = panel?.title ? `${panel.title} · makeROI Analytics` : 'makeROI Analytics';

  // screen-engine defineTable: fetchConfig().then(...) без .catch → при reject
  // configLoading=true навсегда (бесконечный skeleton). Ошибки глотаем и показываем сами.
  const table = defineTable({
    fetchConfig: async () => {
      try {
        return await api.fetchConfig();
      } catch (error: unknown) {
        const message = error instanceof Error ? error.message : String(error);
        console.error('[analytics spa] fetchConfig', error);
        showFatal(`Не удалось загрузить конфиг таблицы.\n${message}`);
        return emptyConfig() as never;
      }
    },
    fetchData: async (params) => {
      try {
        const [data, dashboard] = await Promise.all([
          api.fetchData(params),
          api.fetchMetrics(params).catch(() => ({ metrics: [], chart: [], series: [], top_scenarios: [] })),
        ]);
        setDashboard(dashboard);
        return data;
      } catch (error: unknown) {
        const message = error instanceof Error ? error.message : String(error);
        console.error('[analytics spa] fetchData', error);
        showFatal(`Не удалось загрузить данные таблицы.\n${message}`);
        return emptyData() as never;
      }
    },
    fetchDetail: async (row) => {
      try {
        return await api.fetchDetail!(row);
      } catch (error: unknown) {
        console.error('[analytics spa] fetchDetail', error);
        return {
          error_message: error instanceof Error ? error.message : String(error),
        } as never;
      }
    },
    params: initialTableParams(bootstrap),
    // Хранит ширины колонок; кнопку «Настройки» скрываем в CSS (порядок/видимость не нужны).
    settingsStorage: getLocalStore(),
    extraLayouts: () => [layout('analytics-metrics')],
  });

  defineScreen(() => table.screen.get() as LayoutNode[]).mount('#app');

  // Клик по строке → detail modal (в screen-engine нет onRowClick, openDetail уже есть).
  bindRowOpenDetail(table);
}

function bindRowOpenDetail(table: ReturnType<typeof defineTable>): void {
  const root = document.getElementById('app');
  if (!root) return;

  root.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (
      target.closest(
        'a, button, input, select, textarea, label, [role="button"], [class*="apag"], [class*="header-cell"], [class*="row_header"]',
      )
    ) {
      return;
    }

    const rowEl = target.closest('[class*="table-row"]');
    if (!rowEl || String(rowEl.className).includes('header')) return;

    const parent = rowEl.parentElement;
    if (!parent) return;

    const siblings = [...parent.children].filter(
      (el) => String(el.className).includes('table-row') && !String(el.className).includes('header'),
    );
    const index = siblings.indexOf(rowEl);
    const row = table.rows.value?.[index];
    if (!row || !table.detailConfig.value) return;

    table.openDetail(row);
  });
}

boot().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : String(error);
  console.error('[analytics spa]', error);
  showFatal(message);
});

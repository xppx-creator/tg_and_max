import type { DefineTableOptions, TableFetchParams } from '@makeroi/screen-engine/vue-renderers/Table/Utils/DefineTable';
import type { AnalyticsBootstrap } from '../bootstrap';
import type { AnalyticsDashboard, AnalyticsMetric } from '../metricsState';

type Json = Record<string, unknown>;

const SKIP_BOOTSTRAP_QUERY = new Set(['token', 'lang', 'locale', 'theme', 'section', 'page', 'per_page', 'sort', 'sort_dir']);

function unwrap(json: unknown): Json {
  if (json && typeof json === 'object' && 'success' in json && 'data' in json) {
    return (json as { data: Json }).data;
  }
  return (json ?? {}) as Json;
}

function authHeaders(bootstrap: AnalyticsBootstrap): HeadersInit {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (bootstrap.token) {
    headers.Authorization = `Bearer ${bootstrap.token}`;
  }
  if (bootstrap.locale) {
    headers['x-locale'] = String(bootstrap.locale);
  }
  return headers;
}

async function getJson(url: string, bootstrap: AnalyticsBootstrap): Promise<Json> {
  const response = await fetch(url, { headers: authHeaders(bootstrap) });
  if (!response.ok) {
    if (response.status === 401 || response.status === 403) {
      throw new Error(`HTTP ${response.status} · нет/просрочен токен · ${url}`);
    }
    throw new Error(`HTTP ${response.status} · ${url}`);
  }
  return unwrap(await response.json());
}

function appendBootstrapQuery(qs: URLSearchParams, bootstrap: AnalyticsBootstrap): void {
  const query = bootstrap.query ?? {};
  for (const [key, value] of Object.entries(query)) {
    if (SKIP_BOOTSTRAP_QUERY.has(key)) continue;
    if (value === undefined || value === null || value === '') continue;
    if (qs.has(key)) continue;
    if (typeof value === 'object') continue;
    qs.set(key, String(value));
  }
}

function appendFilters(qs: URLSearchParams, filters: TableFetchParams['filters'] | undefined): void {
  for (const [code, value] of Object.entries(filters ?? {})) {
    if (value === undefined || value === null || value === '') continue;
    if (typeof value === 'object' && !Array.isArray(value)) {
      for (const [sub, subVal] of Object.entries(value as Record<string, unknown>)) {
        if (subVal === undefined || subVal === null || subVal === '') continue;
        qs.set(`filters[${code}][${sub}]`, String(subVal));
      }
      continue;
    }
    if (Array.isArray(value)) {
      value.forEach((item, index) => qs.set(`filters[${code}][${index}]`, String(item)));
      continue;
    }
    qs.set(`filters[${code}]`, String(value));
  }
}

function buildDataQuery(params: TableFetchParams, bootstrap: AnalyticsBootstrap): string {
  const qs = new URLSearchParams();
  qs.set('section', 'data');
  qs.set('page', String(params.page ?? 1));
  qs.set('per_page', String(params.size ?? 50));

  const sorts = params.sorts ?? {};
  const sortEntry = Object.entries(sorts)[0];
  if (sortEntry) {
    qs.set('sort', sortEntry[0]);
    qs.set('sort_dir', String(sortEntry[1]));
  }

  appendFilters(qs, params.filters);
  appendBootstrapQuery(qs, bootstrap);

  return qs.toString();
}

function buildMetricsQuery(params: Pick<TableFetchParams, 'filters'>, bootstrap: AnalyticsBootstrap): string {
  const qs = new URLSearchParams();
  qs.set('section', 'metrics');
  appendFilters(qs, params.filters);
  appendBootstrapQuery(qs, bootstrap);
  return qs.toString();
}

export function initialTableParams(bootstrap: AnalyticsBootstrap): TableFetchParams {
  const filters: Record<string, string> = {};
  const scenarioId = bootstrap.query?.scenario_id;
  if (scenarioId !== undefined && scenarioId !== null && scenarioId !== '') {
    filters.scenario_sel = String(scenarioId);
  }

  return {
    page: 1,
    size: 50,
    filters,
    sorts: {},
  };
}

function buildDetailQuery(id: string | number, bootstrap: AnalyticsBootstrap): string {
  const qs = new URLSearchParams();
  qs.set('section', 'detail');
  qs.set('id', String(id));
  appendBootstrapQuery(qs, bootstrap);
  return qs.toString();
}

export function createPanelApi(
  endpoint: string,
  bootstrap: AnalyticsBootstrap,
): Pick<DefineTableOptions, 'fetchConfig' | 'fetchData' | 'fetchDetail'> & {
  fetchMetrics: (params: Pick<TableFetchParams, 'filters'>) => Promise<AnalyticsDashboard>;
} {
  return {
    async fetchConfig() {
      const qs = new URLSearchParams({ section: 'config' });
      appendBootstrapQuery(qs, bootstrap);
      const data = await getJson(`${endpoint}?${qs}`, bootstrap);
      const columns = ((data.columns as Array<Record<string, unknown>>) ?? []).map((col) => {
        const rawWidth = col.width;
        // min из конфига, 1fr — растягивает на ширину экрана; при узком viewport — горизонтальный скролл.
        let width = 'minmax(120px, 1fr)';
        if (typeof rawWidth === 'number' && Number.isFinite(rawWidth) && rawWidth > 0) {
          const px = Math.round(rawWidth);
          width = `minmax(${px}px, 1fr)`;
        } else if (typeof rawWidth === 'string' && rawWidth.trim() !== '') {
          width = rawWidth;
        }

        return {
          ...col,
          width,
        };
      });

      return {
        columns: columns as never,
        bulkActions: (data.bulkActions as never) ?? [],
        total: Number(data.total ?? 0),
        detail: data.detail as never,
      };
    },
    async fetchData(params) {
      const data = await getJson(`${endpoint}?${buildDataQuery(params, bootstrap)}`, bootstrap);
      return {
        rows: (data.rows as never) ?? [],
        total: Number(data.total ?? 0),
      };
    },
    async fetchDetail(row) {
      const id = row.id;
      if (id === undefined || id === null || id === '') {
        throw new Error('Row id is required for detail');
      }
      const data = await getJson(`${endpoint}?${buildDetailQuery(id as string | number, bootstrap)}`, bootstrap);
      const detail = (data.detail as Record<string, unknown> | undefined) ?? data;
      return detail as never;
    },
    async fetchMetrics(params) {
      const data = await getJson(`${endpoint}?${buildMetricsQuery(params, bootstrap)}`, bootstrap);
      return {
        metrics: Array.isArray(data.metrics) ? (data.metrics as AnalyticsMetric[]) : [],
        chart: Array.isArray(data.chart) ? (data.chart as AnalyticsDashboard['chart']) : [],
        series: Array.isArray(data.series) ? (data.series as AnalyticsDashboard['series']) : [],
        top_scenarios: Array.isArray(data.top_scenarios)
          ? (data.top_scenarios as AnalyticsDashboard['top_scenarios'])
          : [],
      };
    },
  };
}

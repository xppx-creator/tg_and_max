export type AnalyticsPanelBootstrap = {
  slug: string;
  title: string;
  endpoint: string;
};

export type AnalyticsBootstrap = {
  apiBase: string;
  spaBase: string;
  defaultPanel: string | null;
  panels: AnalyticsPanelBootstrap[];
  tableEndpoint?: string | null;
  token?: string | null;
  locale?: string | null;
  theme?: string | null;
  query?: Record<string, unknown>;
};

declare global {
  interface Window {
    __MAKEROI_ANALITYCS__?: AnalyticsBootstrap;
    __COPY_LEADS_ANALYTICS__?: AnalyticsBootstrap;
  }
}

const WINDOW_KEYS = ['__MAKEROI_ANALITYCS__', '__COPY_LEADS_ANALYTICS__'] as const;

export function readBootstrap(): AnalyticsBootstrap {
  for (const key of WINDOW_KEYS) {
    const value = window[key];
    if (value && typeof value === 'object') {
      return value;
    }
  }

  return {
    apiBase: '/api/v1',
    spaBase: '/analytics',
    defaultPanel: null,
    panels: [],
  };
}

const TOKEN_STORAGE_KEY = 'makeroi-analitycs-token';

/**
 * Token приходит в bootstrap из ?token= и сразу вычищается из URL.
 * Без sessionStorage refresh без query → API 401 → вечный skeleton (defineTable без catch).
 */
export function persistBootstrapToken(bootstrap: AnalyticsBootstrap): AnalyticsBootstrap {
  if (typeof sessionStorage === 'undefined') {
    return bootstrap;
  }

  const fromBootstrap = typeof bootstrap.token === 'string' ? bootstrap.token.trim() : '';
  if (fromBootstrap !== '') {
    sessionStorage.setItem(TOKEN_STORAGE_KEY, fromBootstrap);
    return { ...bootstrap, token: fromBootstrap };
  }

  const stored = sessionStorage.getItem(TOKEN_STORAGE_KEY);
  if (stored && stored.trim() !== '') {
    return { ...bootstrap, token: stored.trim() };
  }

  return { ...bootstrap, token: null };
}

export function resolveActivePanel(bootstrap: AnalyticsBootstrap) {
  const panels = Array.isArray(bootstrap.panels) ? bootstrap.panels : [];
  const queryPanel = bootstrap.query?.panel;
  const slug =
    (typeof queryPanel === 'string' && queryPanel !== '' ? queryPanel : null) ||
    bootstrap.defaultPanel ||
    panels[0]?.slug ||
    (bootstrap.tableEndpoint ? String(bootstrap.tableEndpoint).split('/').pop() : null);

  const panel = panels.find((p) => p.slug === slug) ?? panels[0] ?? null;
  const endpointPath = panel?.endpoint || bootstrap.tableEndpoint || '';
  const apiBase = String(bootstrap.apiBase ?? '/api/v1').replace(/\/$/, '');
  const endpoint = endpointPath
    ? `${apiBase}${endpointPath.startsWith('/') ? endpointPath : `/${endpointPath}`}`
    : '';

  return { panel, endpoint, panels };
}

/** Убирает token из URL после чтения bootstrap (app JWT не должен жить в history). */
export function stripTokenFromUrl(): void {
  if (typeof window === 'undefined' || !window.history?.replaceState) return;
  const url = new URL(window.location.href);
  if (!url.searchParams.has('token')) return;
  url.searchParams.delete('token');
  const search = url.searchParams.toString();
  window.history.replaceState(null, '', url.pathname + (search ? `?${search}` : '') + url.hash);
}

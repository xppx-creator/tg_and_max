import { ref } from 'vue';

export type AnalyticsMetric = {
  label: string;
  value: string | number;
  format?: string;
};

export type AnalyticsChartSlice = {
  key: string;
  label: string;
  value: number;
  color?: string;
};

export type AnalyticsSeriesPoint = {
  date: string;
  label: string;
  success: number;
  error: number;
  in_progress?: number;
  total: number;
};

export type AnalyticsScenarioStat = {
  name: string;
  total: number;
  success: number;
  error: number;
  in_progress?: number;
};

export type AnalyticsDashboard = {
  metrics: AnalyticsMetric[];
  chart: AnalyticsChartSlice[];
  series: AnalyticsSeriesPoint[];
  top_scenarios: AnalyticsScenarioStat[];
};

export const metricsItems = ref<AnalyticsMetric[]>([]);
export const chartSlices = ref<AnalyticsChartSlice[]>([]);
export const seriesPoints = ref<AnalyticsSeriesPoint[]>([]);
export const topScenarios = ref<AnalyticsScenarioStat[]>([]);
export const panelTitle = ref('');

export function setPanelTitle(title: string): void {
  panelTitle.value = title.trim();
}

export function setDashboard(dashboard: Partial<AnalyticsDashboard> | AnalyticsMetric[]): void {
  if (Array.isArray(dashboard)) {
    metricsItems.value = dashboard;
    chartSlices.value = [];
    seriesPoints.value = [];
    topScenarios.value = [];
    return;
  }

  metricsItems.value = Array.isArray(dashboard.metrics) ? dashboard.metrics : [];
  chartSlices.value = Array.isArray(dashboard.chart) ? dashboard.chart : [];
  seriesPoints.value = Array.isArray(dashboard.series) ? dashboard.series : [];
  topScenarios.value = Array.isArray(dashboard.top_scenarios) ? dashboard.top_scenarios : [];
}

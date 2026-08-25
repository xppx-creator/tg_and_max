<script setup lang="ts">
import {
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  Legend,
  LinearScale,
  Tooltip,
  type ChartConfiguration,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
  chartSlices,
  metricsItems,
  panelTitle,
  seriesPoints,
  topScenarios,
} from '../metricsState';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Legend, Tooltip);

const COLORS = {
  success: '#34c759',
  error: '#ff3b30',
  in_progress: '#ff9f0a',
} as const;

const collapsed = ref(false);
const expandedByUser = ref(false);
const daysCanvas = ref<HTMLCanvasElement | null>(null);
const scenariosCanvas = ref<HTMLCanvasElement | null>(null);

let tableEl: HTMLElement | null = null;
let bindTimer: ReturnType<typeof setTimeout> | null = null;
let daysChart: Chart | null = null;
let scenariosChart: Chart | null = null;

const chartTotal = computed(() =>
  chartSlices.value.reduce((sum, slice) => sum + Math.max(0, Number(slice.value) || 0), 0),
);

const hasOverview = computed(
  () =>
    metricsItems.value.length > 0
    || chartSlices.value.length > 0
    || seriesPoints.value.length > 0
    || topScenarios.value.length > 0,
);

const showPanels = computed(() => !collapsed.value || expandedByUser.value);

function slicePercent(value: number, total: number): number {
  if (total <= 0) return 0;
  return Math.round((Math.max(0, value) / total) * 1000) / 10;
}

function sliceColor(color?: string): string {
  switch (color) {
    case 'mint':
    case 'success':
      return COLORS.success;
    case 'rose':
    case 'error':
      return COLORS.error;
    case 'apricot':
    case 'in_progress':
      return COLORS.in_progress;
    default:
      return '#8e8e93';
  }
}

function baseOptions(horizontal = false): ChartConfiguration<'bar'>['options'] {
  // Горизонтальный stacked: категории по Y — иначе tooltip с axis=x «липнет» к самому длинному бару.
  const categoryAxis = horizontal ? 'y' : 'x';

  return {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: horizontal ? 'y' : 'x',
    animation: { duration: 280 },
    interaction: {
      mode: 'index',
      axis: categoryAxis,
      intersect: false,
    },
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        mode: 'index',
        axis: categoryAxis,
        intersect: false,
      },
    },
    scales: {
      x: {
        stacked: true,
        grid: { display: !horizontal, color: 'rgba(0,0,0,0.06)' },
        ticks: {
          color: '#8e8e93',
          font: { size: 10 },
          maxRotation: 0,
        },
        border: { display: false },
      },
      y: {
        stacked: true,
        beginAtZero: true,
        grid: { display: horizontal, color: 'rgba(0,0,0,0.06)' },
        ticks: {
          color: '#8e8e93',
          font: { size: 11 },
          precision: 0,
          ...(horizontal
            ? {
                autoSkip: false,
                callback(value) {
                  const label = String(this.getLabelForValue(value as number) ?? '');
                  return label.length > 28 ? `${label.slice(0, 26)}…` : label;
                },
              }
            : {}),
        },
        border: { display: false },
      },
    },
  };
}

function buildDaysConfig(): ChartConfiguration<'bar'> {
  const labels = seriesPoints.value.map((point) => point.label);
  return {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Успех',
          data: seriesPoints.value.map((point) => point.success),
          backgroundColor: COLORS.success,
          borderRadius: 2,
          maxBarThickness: 22,
        },
        {
          label: 'Ошибки',
          data: seriesPoints.value.map((point) => point.error),
          backgroundColor: COLORS.error,
          borderRadius: 2,
          maxBarThickness: 22,
        },
        {
          label: 'В процессе',
          data: seriesPoints.value.map((point) => point.in_progress ?? 0),
          backgroundColor: COLORS.in_progress,
          borderRadius: 2,
          maxBarThickness: 22,
        },
      ],
    },
    options: baseOptions(false),
  };
}

function buildScenariosConfig(): ChartConfiguration<'bar'> {
  const labels = topScenarios.value.map((row) => row.name);
  return {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Успех',
          data: topScenarios.value.map((row) => row.success),
          backgroundColor: COLORS.success,
          borderRadius: 3,
          maxBarThickness: 16,
        },
        {
          label: 'Ошибки',
          data: topScenarios.value.map((row) => row.error),
          backgroundColor: COLORS.error,
          borderRadius: 3,
          maxBarThickness: 16,
        },
        {
          label: 'В процессе',
          data: topScenarios.value.map((row) => row.in_progress ?? 0),
          backgroundColor: COLORS.in_progress,
          borderRadius: 3,
          maxBarThickness: 16,
        },
      ],
    },
    options: {
      ...baseOptions(true),
      plugins: {
        ...baseOptions(true)?.plugins,
        tooltip: {
          mode: 'index',
          axis: 'y',
          intersect: false,
          callbacks: {
            title(items) {
              const index = items[0]?.dataIndex ?? 0;
              return topScenarios.value[index]?.name ?? items[0]?.label ?? '';
            },
            afterBody(items) {
              const index = items[0]?.dataIndex ?? 0;
              const row = topScenarios.value[index];
              return row ? `Всего: ${row.total}` : '';
            },
          },
        },
      },
    },
  };
}

function destroyCharts(): void {
  daysChart?.destroy();
  scenariosChart?.destroy();
  daysChart = null;
  scenariosChart = null;
}

function renderCharts(): void {
  if (!showPanels.value) {
    destroyCharts();
    return;
  }

  if (daysCanvas.value && seriesPoints.value.length) {
    const config = buildDaysConfig();
    if (daysChart) {
      daysChart.data = config.data;
      daysChart.update();
    } else {
      daysChart = new Chart(daysCanvas.value, config);
    }
  } else {
    daysChart?.destroy();
    daysChart = null;
  }

  if (scenariosCanvas.value && topScenarios.value.length) {
    const config = buildScenariosConfig();
    if (scenariosChart) {
      scenariosChart.data = config.data;
      scenariosChart.options = config.options ?? {};
      scenariosChart.update();
    } else {
      scenariosChart = new Chart(scenariosCanvas.value, config);
    }
  } else {
    scenariosChart?.destroy();
    scenariosChart = null;
  }
}

function syncCollapsedFromScroll(): void {
  if (!tableEl) return;
  const next = tableEl.scrollTop > 28;
  if (next !== collapsed.value) {
    collapsed.value = next;
    if (!next) expandedByUser.value = false;
  }
}

function onTableScroll(): void {
  syncCollapsedFromScroll();
}

function bindTableScroll(attempt = 0): void {
  const next = document.querySelector<HTMLElement>('#app [class*="__table__container"]');
  if (!next) {
    if (attempt < 40) {
      bindTimer = setTimeout(() => bindTableScroll(attempt + 1), 100);
    }
    return;
  }

  if (tableEl === next) {
    syncCollapsedFromScroll();
    return;
  }

  tableEl?.removeEventListener('scroll', onTableScroll);
  tableEl = next;
  tableEl.addEventListener('scroll', onTableScroll, { passive: true });
  syncCollapsedFromScroll();
}

function toggleExpanded(): void {
  if (!collapsed.value) return;
  expandedByUser.value = !expandedByUser.value;
}

watch(
  [showPanels, seriesPoints, topScenarios],
  async () => {
    await nextTick();
    renderCharts();
  },
  { deep: true },
);

onMounted(async () => {
  bindTableScroll();
  await nextTick();
  renderCharts();
});

onBeforeUnmount(() => {
  if (bindTimer) clearTimeout(bindTimer);
  tableEl?.removeEventListener('scroll', onTableScroll);
  tableEl = null;
  destroyCharts();
});
</script>

<template>
  <div
    v-if="hasOverview"
    class="analytics-metrics"
    :class="{
      'analytics-metrics_collapsed': collapsed && !expandedByUser,
      'analytics-metrics_expanded': collapsed && expandedByUser,
    }"
  >
    <div class="analytics-metrics__top">
      <div class="analytics-metrics__heading">
        <h1 class="analytics-metrics__title">{{ panelTitle || 'Аналитика' }}</h1>
      </div>

      <div class="analytics-metrics__top-right">
        <div v-if="metricsItems.length" class="analytics-metrics__kpis">
          <div
            v-for="item in metricsItems"
            :key="item.label"
            class="analytics-metrics__kpi"
          >
            <div class="analytics-metrics__label">{{ item.label }}</div>
            <div class="analytics-metrics__value">{{ item.value }}</div>
          </div>
        </div>

        <button
          v-if="collapsed"
          type="button"
          class="analytics-metrics__toggle"
          :aria-expanded="showPanels"
          @click="toggleExpanded"
        >
          {{ expandedByUser ? 'Свернуть' : 'Графики' }}
        </button>
      </div>
    </div>

    <div
      v-if="chartSlices.length && (!showPanels || collapsed)"
      class="analytics-metrics__mini-bar"
      role="img"
      :aria-label="`Всего ${chartTotal}`"
    >
      <div
        v-for="slice in chartSlices"
        :key="`mini-${slice.key}`"
        class="analytics-metrics__bar-seg"
        :style="{
          width: `${slicePercent(slice.value, chartTotal)}%`,
          background: sliceColor(slice.color),
        }"
        :title="`${slice.label}: ${slice.value}`"
      />
    </div>

    <div
      v-if="showPanels && (seriesPoints.length || topScenarios.length)"
      class="analytics-metrics__panels"
    >
      <section v-if="seriesPoints.length" class="analytics-metrics__panel">
        <div class="analytics-metrics__panel-head">
          <div class="analytics-metrics__panel-title">Результаты по дням</div>
          <div v-if="chartSlices.length" class="analytics-metrics__legend analytics-metrics__legend_compact">
            <div
              v-for="slice in chartSlices"
              :key="`legend-${slice.key}`"
              class="analytics-metrics__legend-item"
            >
              <span
                class="analytics-metrics__dot"
                :style="{ background: sliceColor(slice.color) }"
              />
              <span class="analytics-metrics__legend-label">{{ slice.label }}</span>
            </div>
          </div>
        </div>
        <div class="analytics-metrics__chart">
          <canvas ref="daysCanvas" aria-label="Результаты по дням" />
        </div>
      </section>

      <section v-if="topScenarios.length" class="analytics-metrics__panel">
        <div class="analytics-metrics__panel-title">Топ сценариев</div>
        <div class="analytics-metrics__chart analytics-metrics__chart_scenarios">
          <canvas ref="scenariosCanvas" aria-label="Топ сценариев" />
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.analytics-metrics {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  margin: 0;
}

.analytics-metrics__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.analytics-metrics__heading {
  min-width: 0;
  flex: 1 1 auto;
}

.analytics-metrics__title {
  margin: 0;
  color: var(--makeroi-regular-text-color, #000);
  font-size: 18px;
  font-weight: 700;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.analytics-metrics_collapsed .analytics-metrics__title {
  font-size: 16px;
}

.analytics-metrics__top-right {
  display: flex;
  flex: 0 1 auto;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  min-width: 0;
}

.analytics-metrics__kpis {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
  min-width: 0;
}

.analytics-metrics__kpi {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
  padding: 6px 12px;
  border-radius: 6px;
  background: var(--makeroi-surface-raised, #fff);
  border: 1px solid var(--makeroi-background-secondary, #ebebeb);
}

.analytics-metrics_collapsed .analytics-metrics__kpi {
  padding: 4px 10px;
}

.analytics-metrics__label {
  font-size: 11px;
  line-height: 1.2;
  color: var(--makeroi-support-text-color, #a0adbd);
  text-transform: uppercase;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.analytics-metrics__value {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.15;
  color: var(--makeroi-regular-text-color, #000);
  font-variant-numeric: tabular-nums;
}

.analytics-metrics_collapsed .analytics-metrics__value {
  font-size: 14px;
}

.analytics-metrics__toggle {
  flex: 0 0 auto;
  margin: 0;
  padding: 4px 10px;
  border: 1px solid color-mix(in srgb, #366af3 30%, #dbe4ff);
  border-radius: 6px;
  background: color-mix(in srgb, #366af3 8%, #fff);
  color: #366af3;
  font: inherit;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.analytics-metrics__mini-bar {
  display: flex;
  width: 100%;
  height: 6px;
  overflow: hidden;
  border-radius: 999px;
  background: var(--makeroi-background-secondary, #eef1f5);
}

.analytics-metrics__bar-seg {
  min-width: 0;
  height: 100%;
}

.analytics-metrics__panels {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: 10px;
  align-items: stretch;
}

.analytics-metrics__panel {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 100%;
  height: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  background: var(--makeroi-surface-raised, #fff);
  border: 1px solid var(--makeroi-background-secondary, #ebebeb);
  box-sizing: border-box;
}

.analytics-metrics__panel-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.analytics-metrics__panel-title {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--makeroi-support-text-color, #5f6c78);
}

.analytics-metrics__panel-head .analytics-metrics__panel-title {
  margin-bottom: 0;
}

.analytics-metrics__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
}

.analytics-metrics__legend_compact {
  gap: 6px 10px;
}

.analytics-metrics__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.analytics-metrics__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.analytics-metrics__legend-label {
  color: var(--makeroi-support-text-color, #5f6c78);
}

.analytics-metrics__chart {
  position: relative;
  flex: 1 1 auto;
  width: 100%;
  min-height: 140px;
  height: 140px;
}

.analytics-metrics__chart_scenarios {
  min-height: 160px;
  height: 160px;
}

@media (max-width: 860px) {
  .analytics-metrics__panels {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>

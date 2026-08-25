<script setup lang="ts">
import { computed, ref } from 'vue';

export type ActionLogItem = {
  time?: string | null;
  text?: string | null;
  context?: string | Record<string, unknown> | null;
  level?: string | null;
};

const props = withDefaults(
  defineProps<{
    title?: string;
    items?: ActionLogItem[] | null;
  }>(),
  {
    title: 'Лог действий',
    items: () => [],
  },
);

const openIndex = ref<number | null>(null);

const rows = computed(() =>
  (props.items ?? []).map((item) => {
    const contextText = formatContext(item.context);
    return {
      time: String(item.time ?? '').trim(),
      text: String(item.text ?? '').trim() || '—',
      contextText,
      expandable: contextText !== '',
      isError: ['error', 'critical', 'alert', 'emergency'].includes(
        String(item.level ?? '').toLowerCase(),
      ),
    };
  }),
);

function formatContext(context: ActionLogItem['context']): string {
  if (context == null || context === '') return '';
  if (typeof context === 'string') return context.trim();
  if (typeof context === 'object' && Object.keys(context).length === 0) return '';
  try {
    return JSON.stringify(context, null, 2);
  } catch {
    return String(context);
  }
}

function toggle(index: number, expandable: boolean): void {
  if (!expandable) return;
  openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
  <section v-if="rows.length > 0" class="action-log">
    <header class="action-log__header">{{ title }}</header>
    <ul class="action-log__list">
      <li
        v-for="(row, index) in rows"
        :key="index"
        class="action-log__item"
        :class="{
          'action-log__item_error': row.isError,
          'action-log__item_open': openIndex === index,
          'action-log__item_expandable': row.expandable,
        }"
      >
        <button
          type="button"
          class="action-log__line"
          :disabled="!row.expandable"
          @click="toggle(index, row.expandable)"
        >
          <span v-if="row.time" class="action-log__time">{{ row.time }}</span>
          <span class="action-log__text">{{ row.text }}</span>
          <span v-if="row.expandable" class="action-log__chevron" aria-hidden="true">
            {{ openIndex === index ? '▾' : '▸' }}
          </span>
        </button>
        <pre v-if="row.expandable && openIndex === index" class="action-log__context">{{ row.contextText }}</pre>
      </li>
    </ul>
  </section>
</template>

<style scoped lang="scss">
.action-log {
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px solid color-mix(in srgb, currentColor 12%, transparent);
}

.action-log__header {
  margin: 0 0 10px;
  color: color-mix(in srgb, currentColor 62%, transparent);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.04em;
  line-height: 1.2;
  text-transform: uppercase;
}

.action-log__list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.action-log__item {
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
}

.action-log__line {
  display: flex;
  align-items: baseline;
  gap: 10px;
  width: 100%;
  margin: 0;
  padding: 4px 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  font-size: 13px;
  line-height: 1.35;
  text-align: left;
  cursor: default;
}

.action-log__item_expandable .action-log__line {
  cursor: pointer;
  border-radius: 4px;
}

.action-log__item_expandable .action-log__line:hover {
  background: color-mix(in srgb, currentColor 4%, transparent);
}

.action-log__time {
  flex: 0 0 auto;
  min-width: 58px;
  color: color-mix(in srgb, currentColor 48%, transparent);
  font-variant-numeric: tabular-nums;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 12px;
}

.action-log__text {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.action-log__item_error .action-log__text {
  color: #c62828;
}

.action-log__chevron {
  flex: 0 0 auto;
  color: color-mix(in srgb, currentColor 45%, transparent);
  font-size: 11px;
}

.action-log__context {
  margin: 0 0 6px;
  padding: 8px 10px 8px 68px;
  border: 0;
  border-radius: 4px;
  background: color-mix(in srgb, currentColor 4%, transparent);
  color: color-mix(in srgb, currentColor 72%, transparent);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 11px;
  line-height: 1.45;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>

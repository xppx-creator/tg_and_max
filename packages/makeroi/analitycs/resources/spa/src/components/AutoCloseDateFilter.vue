<script setup lang="ts">
import { nextTick, useTemplateRef, watch } from 'vue';
import { DatePicker, DatePickerMode } from '@makeroi/components';

const props = defineProps<{
  filter?: {
    title?: string;
    settings?: {
      format?: string;
      placeholder?: string;
    };
  };
  value?: unknown;
  el?: unknown;
  mode?: 'single' | 'range';
}>();

const emit = defineEmits<{
  'update:value': [value: unknown];
  'update:el': [el: unknown];
}>();

type DatePickerExpose = {
  listEl?: unknown;
  close?: (() => void) | { value?: () => void };
};

const datePickerRef = useTemplateRef<DatePickerExpose>('date-picker');
const pickerMode = props.mode === 'range' ? DatePickerMode.Range : DatePickerMode.Single;

watch(
  () => datePickerRef.value?.listEl,
  (el) => emit('update:el', el),
);

function isComplete(value: unknown): boolean {
  if (props.mode === 'range') {
    return Array.isArray(value) && value.length >= 2 && value[0] != null && value[1] != null;
  }
  return value != null && value !== '';
}

function closePicker(): void {
  const close = datePickerRef.value?.close;
  if (typeof close === 'function') {
    close();
    return;
  }
  if (close && typeof close.value === 'function') {
    close.value();
  }
}

function onUpdateDate(value: unknown): void {
  emit('update:value', value);
  if (!isComplete(value)) return;
  void nextTick(() => closePicker());
}
</script>

<template>
  <label class="analytics-date-filter">
    <span v-if="filter?.title" class="analytics-date-filter__title">{{ filter.title }}</span>
    <DatePicker
      ref="date-picker"
      :mode="pickerMode"
      :date="value"
      :format="filter?.settings?.format"
      :placeholder="filter?.settings?.placeholder"
      @update:date="onUpdateDate"
    />
  </label>
</template>

<style scoped lang="scss">
.analytics-date-filter {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.analytics-date-filter__title {
  margin: 0;
  color: var(--makeroi-support-text-color, #5f6c78);
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  text-transform: uppercase;
}
</style>

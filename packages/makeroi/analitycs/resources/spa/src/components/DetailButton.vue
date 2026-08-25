<script setup lang="ts">
defineProps<{
  text?: string;
  onClick?: () => void;
}>();
</script>

<template>
  <button
    type="button"
    class="analytics-detail-btn"
    @click.stop="onClick?.()"
  >
    {{ text || 'Подробнее' }}
  </button>
</template>

<style scoped lang="scss">
.analytics-detail-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 4px 10px;
  border: 1px solid color-mix(in srgb, #366af3 35%, #dbe4ff);
  border-radius: 6px;
  background: color-mix(in srgb, #366af3 8%, #fff);
  color: #366af3;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  cursor: pointer;
}

.analytics-detail-btn:hover {
  background: color-mix(in srgb, #366af3 14%, #fff);
  border-color: #366af3;
}
</style>

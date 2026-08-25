<script setup lang="ts">
import { computed, toValue } from 'vue';
import { Skeleton } from '@makeroi/components';

/**
 * Скелетон тела detail-модалки (template-slot API).
 * Пока context.loading=true — не рендерим children: иначе Vue toDisplayString
 * мигает JSON по объектным ячейкам строки (initiator / lead и т.п.).
 */
const props = defineProps<{
  loading?: boolean;
}>();

const isLoading = computed(() => Boolean(toValue(props.loading)));

const schema = {
  direction: 'column' as const,
  gap: '18px',
  width: '100%',
  items: [
    {
      type: 'group' as const,
      direction: 'column' as const,
      gap: '10px',
      padding: '14px 16px',
      width: '100%',
      items: [
        { type: 'line' as const, width: '46%', height: '16px', shape: 'rounded' as const },
        { type: 'line' as const, width: '88%', height: '12px', shape: 'rounded' as const },
        { type: 'line' as const, width: '72%', height: '12px', shape: 'rounded' as const },
      ],
    },
    {
      type: 'group' as const,
      direction: 'column' as const,
      gap: '12px',
      width: '100%',
      items: [
        { type: 'line' as const, width: '34%', height: '12px', shape: 'rounded' as const },
        {
          type: 'repeat' as const,
          count: 6,
          gap: '10px',
          direction: 'column' as const,
          width: '100%',
          item: {
            type: 'group' as const,
            direction: 'row' as const,
            gap: '16px',
            width: '100%',
            items: [
              { type: 'line' as const, width: '150px', height: '14px', shape: 'rounded' as const },
              { type: 'line' as const, flex: 1, height: '14px', shape: 'rounded' as const },
            ],
          },
        },
      ],
    },
    {
      type: 'group' as const,
      direction: 'column' as const,
      gap: '10px',
      width: '100%',
      items: [
        { type: 'line' as const, width: '28%', height: '12px', shape: 'rounded' as const },
        {
          type: 'repeat' as const,
          count: 4,
          gap: '8px',
          direction: 'column' as const,
          width: '100%',
          item: {
            type: 'group' as const,
            direction: 'row' as const,
            gap: '12px',
            width: '100%',
            items: [
              { type: 'line' as const, width: '58px', height: '12px', shape: 'rounded' as const },
              { type: 'line' as const, flex: 1, height: '12px', shape: 'rounded' as const },
            ],
          },
        },
      ],
    },
  ],
};
</script>

<template>
  <Skeleton
    v-if="isLoading"
    class="modal-skeleton-loader"
    :schema="(schema as any)"
    animated
  />
  <div v-else class="modal-skeleton-loader__body">
    <slot />
  </div>
</template>

<style scoped lang="scss">
.modal-skeleton-loader {
  width: 100%;
  min-height: 280px;
}

.modal-skeleton-loader__body {
  width: 100%;
  min-width: 0;
}
</style>

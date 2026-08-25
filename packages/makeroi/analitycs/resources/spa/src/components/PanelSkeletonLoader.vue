<script setup lang="ts">
import { computed, ref, toValue, watch } from 'vue';
import { Skeleton } from '@makeroi/components';
import { LayoutRenderer } from '@makeroi/screen-engine';
import type { LayoutNode } from '@makeroi/screen-engine';

const props = defineProps<{
  loading?: boolean | Promise<unknown>;
  children?: LayoutNode[];
  size?: string;
  color?: string;
}>();

const emit = defineEmits<{ resolved: [] }>();

const loading = ref(true);

const initialLoading = toValue(props.loading);
if (typeof initialLoading === 'boolean') {
  watch(
    () => props.loading,
    (value) => {
      loading.value = Boolean(toValue(value));
    },
    { deep: true, immediate: true },
  );
} else if (initialLoading && typeof (initialLoading as Promise<unknown>).then === 'function') {
  (initialLoading as Promise<unknown>).then(() => {
    loading.value = false;
  });
}

watch(loading, (value) => {
  if (value) emit('resolved');
});

const schema = computed(() => ({
  direction: 'column' as const,
  gap: '16px',
  width: '100%',
  items: [
    {
      type: 'group' as const,
      direction: 'row' as const,
      gap: '12px',
      width: '100%',
      items: [
        {
          type: 'repeat' as const,
          count: 3,
          gap: '12px',
          direction: 'row' as const,
          flex: 1,
          item: {
            type: 'group' as const,
            direction: 'column' as const,
            gap: '10px',
            padding: '14px 16px',
            flex: 1,
            items: [
              { type: 'line' as const, width: '42%', height: '12px', shape: 'rounded' as const },
              { type: 'line' as const, width: '28%', height: '22px', shape: 'rounded' as const },
            ],
          },
        },
      ],
    },
    {
      type: 'group' as const,
      direction: 'row' as const,
      justify: 'flex-end',
      width: '100%',
      items: [{ type: 'line' as const, width: '110px', height: '32px', shape: 'rounded' as const }],
    },
    {
      type: 'group' as const,
      direction: 'column' as const,
      gap: '0',
      width: '100%',
      items: [
        {
          type: 'group' as const,
          direction: 'row' as const,
          gap: '12px',
          padding: '12px 14px',
          width: '100%',
          items: [
            {
              type: 'repeat' as const,
              count: 8,
              gap: '12px',
              direction: 'row' as const,
              flex: 1,
              item: { type: 'line' as const, height: '36px', shape: 'rounded' as const, flex: 1 },
            },
          ],
        },
        {
          type: 'repeat' as const,
          count: 6,
          gap: '0',
          direction: 'column' as const,
          width: '100%',
          item: {
            type: 'group' as const,
            direction: 'row' as const,
            gap: '12px',
            padding: '14px',
            width: '100%',
            items: [
              {
                type: 'repeat' as const,
                count: 8,
                gap: '12px',
                direction: 'row' as const,
                flex: 1,
                item: { type: 'line' as const, height: '14px', shape: 'rounded' as const, flex: 1 },
              },
            ],
          },
        },
      ],
    },
  ],
}));
</script>

<template>
  <Skeleton
    v-if="loading"
    class="panel-skeleton-loader"
    :schema="(schema as any)"
    animated
  />
  <template v-else-if="children?.length">
    <LayoutRenderer
      v-for="(child, i) in children"
      :key="i"
      :layout="child"
    />
  </template>
</template>

<style scoped lang="scss">
.panel-skeleton-loader {
  width: 100%;
  min-height: 320px;
}
</style>

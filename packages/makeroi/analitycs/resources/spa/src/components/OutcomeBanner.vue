<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string | null;
    description?: string | null;
    color?: string | null;
  }>(),
  {
    title: '',
    description: '',
    color: 'grey',
  },
);
</script>

<template>
  <div
    v-if="title || description"
    class="outcome-banner"
    :class="[`outcome-banner_${color || 'grey'}`]"
  >
    <p v-if="title" class="outcome-banner__title">{{ title }}</p>
    <p v-if="description" class="outcome-banner__description">{{ description }}</p>
  </div>
</template>

<style scoped lang="scss">
.outcome-banner {
  --primary-color: var(--makeroi-main-grey, #5f6c78);
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-sizing: border-box;
  width: 100%;
  padding: 10px 12px;
  border-radius: 3px;
  color: #5f6c78;
  background-color: color-mix(in srgb, var(--primary-color) 15%, transparent);
}

.outcome-banner__title {
  margin: 0;
  color: var(--makeroi-regular-text-color, #000);
  font-size: 13px;
  font-weight: 700;
  line-height: 16px;
}

.outcome-banner__description {
  margin: 0;
  font-size: 15px;
  font-weight: 400;
  line-height: 20px;
  white-space: pre-wrap;
  word-break: break-word;
}

.outcome-banner_green {
  --primary-color: var(--makeroi-main-green, #2e7d32);
}

.outcome-banner_yellow {
  --primary-color: var(--makeroi-main-yellow, #f0a202);
}

.outcome-banner_blue {
  --primary-color: #366af3;
}

.outcome-banner_red {
  --primary-color: var(--makeroi-main-red, #c62828);
}

.outcome-banner_grey {
  --primary-color: var(--makeroi-main-grey, #5f6c78);
}
</style>

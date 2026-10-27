<script setup lang="ts">
import AppIcon from './AppIcon.vue'

defineProps<{ title: string; prevLabel: string; nextLabel: string }>()
const emit = defineEmits<{ prev: []; next: [] }>()
</script>

<template>
  <nav class="flex items-center justify-between gap-2">
    <button
      type="button"
      class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-white text-ink transition hover:bg-primary-soft"
      :aria-label="prevLabel"
      @click="emit('prev')"
    >
      <AppIcon name="chevron-left" :size="18" />
    </button>
    <h2 class="text-center text-base font-semibold text-balance" aria-live="polite">{{ title }}</h2>
    <button
      type="button"
      class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-white text-ink transition hover:bg-primary-soft"
      :aria-label="nextLabel"
      @click="emit('next')"
    >
      <AppIcon name="chevron-right" :size="18" />
    </button>
  </nav>
</template>

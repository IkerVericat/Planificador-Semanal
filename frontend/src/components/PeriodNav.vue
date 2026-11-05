<script setup lang="ts">
import AppIcon from './AppIcon.vue'

defineProps<{
  title: string
  prevLabel: string
  nextLabel: string
  /** Muestra "Hoy" cuando el periodo visible no contiene el día de hoy */
  showToday?: boolean
}>()
const emit = defineEmits<{ prev: []; next: []; today: [] }>()
</script>

<template>
  <nav class="flex items-center justify-between gap-2">
    <button
      type="button"
      class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-card text-ink transition hover:bg-primary-soft"
      :aria-label="prevLabel"
      :title="`${prevLabel} (←)`"
      aria-keyshortcuts="ArrowLeft"
      @click="emit('prev')"
    >
      <AppIcon name="chevron-left" :size="18" />
    </button>

    <div class="flex min-w-0 flex-wrap items-center justify-center gap-x-2">
      <h2 class="text-center text-base font-semibold text-balance" aria-live="polite">{{ title }}</h2>
      <button
        v-if="showToday"
        type="button"
        class="flex min-h-11 items-center"
        title="Ir a hoy (T)"
        aria-keyshortcuts="T"
        @click="emit('today')"
      >
        <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-primary hover:bg-primary/15">
          Hoy
        </span>
      </button>
    </div>

    <button
      type="button"
      class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-card text-ink transition hover:bg-primary-soft"
      :aria-label="nextLabel"
      :title="`${nextLabel} (→)`"
      aria-keyshortcuts="ArrowRight"
      @click="emit('next')"
    >
      <AppIcon name="chevron-right" :size="18" />
    </button>
  </nav>
</template>

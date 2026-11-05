<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { usePlannerStore } from '@/stores/planner'
import AppIcon from './AppIcon.vue'

/** Tiempo que se ve el aviso */
const DURATION_MS = 5000

const planner = usePlannerStore()
let timer = 0

watch(
  () => planner.toast?.id,
  (id) => {
    window.clearTimeout(timer)
    if (id !== undefined) timer = window.setTimeout(() => planner.dismissToast(id), DURATION_MS)
  },
)

onBeforeUnmount(() => window.clearTimeout(timer))
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-0 bottom-[max(6rem,calc(env(safe-area-inset-bottom)+5rem))] z-40 flex justify-center px-4 lg:bottom-8"
    role="status"
    aria-live="polite"
  >
    <Transition name="toast">
      <div
        v-if="planner.toast"
        :key="planner.toast.id"
        class="pointer-events-auto flex min-h-12 max-w-md items-center gap-3 rounded-xl bg-ink py-1.5 pl-4 pr-1.5 text-sm text-on-primary shadow-lg"
      >
        <span>{{ planner.toast.message }}</span>
        <button
          v-if="planner.toast.undo"
          type="button"
          class="min-h-9 rounded-lg px-3 font-semibold text-accent hover:bg-white/10 disabled:opacity-60"
          :disabled="planner.undoing"
          title="Deshacer (Ctrl+Z)"
          aria-keyshortcuts="Control+Z Meta+Z"
          @click="planner.undoToast()"
        >
          Deshacer
        </button>
        <button
          v-else
          type="button"
          class="flex size-9 items-center justify-center rounded-lg text-on-primary/70 hover:bg-white/10"
          aria-label="Cerrar aviso"
          @click="planner.dismissToast(planner.toast.id)"
        >
          <AppIcon name="close" :size="16" />
        </button>
      </div>
    </Transition>
  </div>
</template>

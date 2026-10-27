<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps<{ open: boolean; title: string }>()
const emit = defineEmits<{ close: [] }>()

const titleId = useId()
const panel = ref<HTMLElement | null>(null)
let previousFocus: HTMLElement | null = null

watch(
  () => props.open,
  async (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) {
      previousFocus = document.activeElement as HTMLElement | null
      await nextTick()
      panel.value?.focus()
    } else {
      previousFocus?.focus()
    }
  },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="fixed inset-0 z-40 bg-ink/40" aria-hidden="true" @click="emit('close')" />
    </Transition>
    <Transition name="sheet">
      <div
        v-if="open"
        ref="panel"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        tabindex="-1"
        class="fixed inset-x-0 bottom-0 z-50 mx-auto max-h-[92dvh] max-w-md overflow-y-auto rounded-t-3xl bg-white px-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-3 shadow-2xl focus:outline-none md:inset-y-0 md:my-auto md:h-fit md:max-h-[85dvh] md:max-w-lg md:rounded-3xl md:px-6 md:pb-6 md:pt-5"
        @keydown.esc="emit('close')"
      >
        <!-- En tablet y ordenador es una ventana centrada: sin asa de arrastre -->
        <div class="mx-auto mb-2 h-1.5 w-10 rounded-full bg-line md:hidden" aria-hidden="true" />
        <div class="mb-4 flex items-center justify-between gap-4">
          <h2 :id="titleId" class="text-lg font-semibold">{{ title }}</h2>
          <button
            type="button"
            class="-mr-2 flex size-11 items-center justify-center rounded-full text-muted hover:bg-surface"
            aria-label="Cerrar"
            @click="emit('close')"
          >
            <AppIcon name="close" />
          </button>
        </div>
        <slot />
      </div>
    </Transition>
  </Teleport>
</template>

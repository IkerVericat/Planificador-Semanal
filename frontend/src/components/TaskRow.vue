<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Occurrence } from '@/types/api'
import { formatTime12h } from '@/utils/dates'
import AppIcon from './AppIcon.vue'

const props = defineProps<{
  occurrence: Occurrence
  color: string
  /** En ordenador, un solo bloque con la hora encima del título (columnas estrechas de la vista Semana) */
  stackOnDesktop?: boolean
  /** Se puede arrastrar a otro día */
  draggable?: boolean
  /** Es la copia que se está arrastrando (se atenúa el original) */
  dragging?: boolean
}>()
const emit = defineEmits<{
  select: [occurrence: Occurrence]
  toggle: [occurrence: Occurrence]
  dragStart: [event: PointerEvent, element: HTMLElement]
}>()

const root = ref<HTMLElement | null>(null)

const time = computed(() =>
  props.occurrence.start_time ? formatTime12h(props.occurrence.start_time) : 'Todo el día',
)

const accessibleLabel = computed(() => {
  const parts = [time.value, props.occurrence.title]
  if (props.occurrence.is_completed) parts.push('hecha')
  if (props.occurrence.is_recurring) parts.push('se repite')
  return parts.join(', ')
})

function onPointerDown(event: PointerEvent): void {
  if (props.draggable && root.value) emit('dragStart', event, root.value)
}
</script>

<template>
  <div
    ref="root"
    class="relative rounded-lg transition-opacity"
    :class="{ 'opacity-30': dragging }"
    :style="{ '--category': color }"
  >
    <button
      type="button"
      data-task-main
      class="flex w-full gap-2 rounded-lg text-left text-ink transition active:scale-[0.99]"
      :class="[
        { 'opacity-50': occurrence.is_completed },
        draggable && 'cursor-grab touch-manipulation select-none [-webkit-touch-callout:none]',
        stackOnDesktop && 'lg:flex-col lg:gap-0.5 lg:bg-(--category) lg:px-2.5 lg:py-2 lg:hover:brightness-95',
      ]"
      :aria-label="accessibleLabel"
      @click="emit('select', occurrence)"
      @pointerdown="onPointerDown"
    >
      <span
        class="flex min-h-11 w-[68px] shrink-0 items-center justify-center rounded-lg bg-(--category) px-1 text-center font-semibold leading-tight"
        :class="[
          occurrence.start_time ? 'whitespace-nowrap text-[12px]' : 'text-[11px]',
          stackOnDesktop && 'lg:min-h-0 lg:w-auto lg:justify-start lg:p-0 lg:pr-6 lg:text-[11px] lg:opacity-75',
        ]"
      >
        {{ time }}
        <!-- En columnas estrechas el icono de repetición va junto a la hora, lejos del círculo -->
        <AppIcon
          v-if="occurrence.is_recurring && stackOnDesktop"
          name="repeat"
          :size="12"
          class="ml-1 hidden shrink-0 lg:inline"
        />
      </span>
      <!-- pr deja sitio al círculo de completar -->
      <span
        class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-lg bg-(--category) py-2 pl-3 pr-11 text-[13px] font-bold uppercase tracking-wide"
        :class="stackOnDesktop && 'lg:min-h-0 lg:items-start lg:py-0 lg:pl-0 lg:pr-6 lg:text-[12px] lg:leading-snug lg:tracking-normal'"
      >
        <span class="min-w-0 flex-1 break-words hyphens-auto" :class="{ 'line-through': occurrence.is_completed }">
          {{ occurrence.title }}
        </span>
        <AppIcon
          v-if="occurrence.is_recurring"
          name="repeat"
          :size="14"
          class="shrink-0 opacity-50"
          :class="stackOnDesktop && 'lg:hidden'"
        />
      </span>
    </button>

    <!-- Completar con un toque, sin abrir el formulario -->
    <button
      type="button"
      data-task-toggle
      class="group absolute right-0 top-0 flex h-full w-11 items-center justify-center rounded-r-lg"
      :class="stackOnDesktop && 'lg:right-1 lg:top-1 lg:size-7 lg:h-7'"
      :aria-label="occurrence.is_completed ? `Marcar como pendiente: ${occurrence.title}` : `Marcar como hecha: ${occurrence.title}`"
      :aria-pressed="occurrence.is_completed"
      :title="occurrence.is_completed ? 'Marcar como pendiente' : 'Marcar como hecha'"
      @click.stop="emit('toggle', occurrence)"
      @pointerdown.stop
    >
      <span
        class="flex size-5 items-center justify-center rounded-full border-2 transition"
        :class="
          occurrence.is_completed
            ? 'border-transparent bg-ink/75 text-white'
            : 'border-ink/35 bg-white/50 text-transparent group-hover:border-ink/60 group-hover:text-ink/50'
        "
      >
        <AppIcon name="check" :size="12" />
      </span>
    </button>
  </div>
</template>

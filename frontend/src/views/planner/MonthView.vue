<script setup lang="ts">
import { computed } from 'vue'
import { endOfMonth, isSameDay, isToday, startOfMonth } from 'date-fns'
import PeriodNav from '@/components/PeriodNav.vue'
import TaskRow from '@/components/TaskRow.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useOccurrences } from '@/composables/useOccurrences'
import { useTaskActions } from '@/composables/useTaskActions'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import {
  WEEKDAY_INITIALS,
  formatDayLong,
  formatMonthYear,
  formatTaskCount,
  monthGrid,
  toISODate,
} from '@/utils/dates'

// Máximo de puntos de color bajo cada día
const MAX_DOTS = 4

const planner = usePlannerStore()
const categories = useCategoriesStore()
// El día de referencia es también el día seleccionado
const { anchor: selected, isCurrentPeriod, setAnchor, shift, goToday } = useAnchorDate()
const { onDragStart, onToggle, isDragging, isDropTarget } = useTaskActions()

const from = computed(() => toISODate(startOfMonth(selected.value)))
const to = computed(() => toISODate(endOfMonth(selected.value)))
const weeks = computed(() => monthGrid(selected.value))

const { byDay, loading, error } = useOccurrences(from, to)

/** Un punto por categoría distinta del día */
function dotsFor(day: Date): string[] {
  const colors = (byDay.value.get(toISODate(day)) ?? []).map((o) => categories.colorFor(o.category_id))
  return [...new Set(colors)].slice(0, MAX_DOTS)
}

const selectedTasks = computed(() => byDay.value.get(toISODate(selected.value)) ?? [])

function dayLabel(day: Date): string {
  const count = byDay.value.get(toISODate(day))?.length ?? 0
  return `${formatDayLong(day)}, ${count ? formatTaskCount(count) : 'sin tareas'}`
}
</script>

<template>
  <section aria-label="Mes">
    <PeriodNav
      :title="formatMonthYear(selected)"
      prev-label="Mes anterior"
      next-label="Mes siguiente"
      :show-today="!isCurrentPeriod"
      @prev="shift(-1)"
      @next="shift(1)"
      @today="goToday"
    />

    <p v-if="error" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

    <!-- Ordenador: calendario a la izquierda y lista del día a la derecha -->
    <div class="lg:mt-4 lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-6">
      <div class="mt-4 rounded-2xl border border-line bg-card px-2 pb-2 pt-3 md:px-3 lg:mt-0" :aria-busy="loading">
        <table class="w-full table-fixed border-separate border-spacing-y-1" role="grid" :aria-label="formatMonthYear(selected)">
          <thead>
            <tr>
              <th v-for="initial in WEEKDAY_INITIALS" :key="initial" scope="col" class="pb-2 text-xs font-medium text-muted">
                {{ initial }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(week, index) in weeks" :key="index">
              <td v-for="(day, dayIndex) in week" :key="dayIndex" class="p-0 text-center">
                <button
                  v-if="day"
                  type="button"
                  class="mx-auto flex h-12 w-11 flex-col items-center justify-center gap-1 rounded-xl text-[15px] transition md:h-16 md:w-full md:max-w-24 md:gap-1.5 md:text-base lg:h-20"
                  :class="
                    isDropTarget(toISODate(day))
                      ? 'bg-primary-soft font-semibold text-primary ring-2 ring-primary'
                      : isSameDay(day, selected)
                        ? 'bg-primary font-semibold text-on-primary'
                        : isToday(day)
                          ? 'font-bold text-primary hover:bg-primary-soft'
                          : 'text-ink hover:bg-surface'
                  "
                  :data-drop-date="toISODate(day)"
                  :aria-pressed="isSameDay(day, selected)"
                  :aria-label="dayLabel(day)"
                  @click="setAnchor(day)"
                >
                  <span class="leading-none">{{ day.getDate() }}</span>
                  <span class="flex h-1.5 gap-0.5 md:h-2" aria-hidden="true">
                    <span
                      v-for="color in dotsFor(day)"
                      :key="color"
                      class="size-1.5 rounded-full md:size-2"
                      :style="{ backgroundColor: color }"
                    />
                  </span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <section
        class="mt-4 lg:sticky lg:top-6 lg:mt-0 lg:rounded-2xl lg:border lg:border-line lg:bg-card lg:p-4"
        aria-live="polite"
      >
        <header class="mb-3 flex items-baseline justify-between">
          <h3 class="font-semibold">{{ formatDayLong(selected) }}</h3>
          <span class="text-[13px] text-muted">{{ formatTaskCount(selectedTasks.length) }}</span>
        </header>

        <ul v-if="selectedTasks.length" class="space-y-2">
          <li v-for="occurrence in selectedTasks" :key="occurrence.task_id + occurrence.original_date">
            <TaskRow
              :occurrence="occurrence"
              :color="categories.colorFor(occurrence.category_id)"
              draggable
              :dragging="isDragging(occurrence)"
              @select="planner.openEdit"
              @toggle="onToggle"
              @drag-start="(event, element) => onDragStart(occurrence, event, element)"
            />
          </li>
        </ul>
        <button
          v-else-if="!loading"
          type="button"
          class="min-h-11 w-full rounded-lg border border-dashed border-line text-sm text-muted transition hover:border-primary hover:text-primary"
          @click="planner.openCreate(toISODate(selected))"
        >
          Sin tareas · Añadir
        </button>
      </section>
    </div>
  </section>
</template>

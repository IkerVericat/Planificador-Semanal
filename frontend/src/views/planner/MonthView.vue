<script setup lang="ts">
import { computed } from 'vue'
import { addMonths, endOfMonth, isSameDay, isToday, startOfMonth } from 'date-fns'
import PeriodNav from '@/components/PeriodNav.vue'
import TaskRow from '@/components/TaskRow.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useLoader } from '@/composables/useLoader'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import type { Occurrence } from '@/types/api'
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
const { anchor: selected, setAnchor } = useAnchorDate()

const from = computed(() => toISODate(startOfMonth(selected.value)))
const to = computed(() => toISODate(endOfMonth(selected.value)))
const weeks = computed(() => monthGrid(selected.value))

const { data: occurrences, loading, error } = useLoader(
  [from, to, () => planner.revision],
  () => planner.fetchOccurrences(from.value, to.value),
  [] as Occurrence[],
)

const byDay = computed(() => {
  const groups = new Map<string, Occurrence[]>()
  for (const occurrence of occurrences.value) {
    groups.set(occurrence.date, [...(groups.get(occurrence.date) ?? []), occurrence])
  }
  return groups
})

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
      @prev="setAnchor(addMonths(selected, -1))"
      @next="setAnchor(addMonths(selected, 1))"
    />

    <div class="mt-4 rounded-2xl border border-line bg-white px-2 pb-2 pt-3" :aria-busy="loading">
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
                class="mx-auto flex h-12 w-11 flex-col items-center justify-center gap-1 rounded-xl text-[15px] transition"
                :class="
                  isSameDay(day, selected)
                    ? 'bg-primary font-semibold text-white'
                    : isToday(day)
                      ? 'font-bold text-primary hover:bg-primary-soft'
                      : 'text-ink hover:bg-surface'
                "
                :aria-pressed="isSameDay(day, selected)"
                :aria-label="dayLabel(day)"
                @click="setAnchor(day)"
              >
                <span class="leading-none">{{ day.getDate() }}</span>
                <span class="flex h-1.5 gap-0.5" aria-hidden="true">
                  <span
                    v-for="color in dotsFor(day)"
                    :key="color"
                    class="size-1.5 rounded-full"
                    :style="{ backgroundColor: color }"
                  />
                </span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="error" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

    <section class="mt-4" aria-live="polite">
      <header class="mb-3 flex items-baseline justify-between">
        <h3 class="font-semibold">{{ formatDayLong(selected) }}</h3>
        <span class="text-[13px] text-muted">{{ formatTaskCount(selectedTasks.length) }}</span>
      </header>

      <ul v-if="selectedTasks.length" class="space-y-2">
        <li v-for="occurrence in selectedTasks" :key="occurrence.task_id">
          <TaskRow
            :occurrence="occurrence"
            :color="categories.colorFor(occurrence.category_id)"
            @select="planner.openEdit"
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
  </section>
</template>

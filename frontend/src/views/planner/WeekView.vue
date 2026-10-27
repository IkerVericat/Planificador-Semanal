<script setup lang="ts">
import { computed } from 'vue'
import { addDays, isToday } from 'date-fns'
import CategoryChip from '@/components/CategoryChip.vue'
import PeriodNav from '@/components/PeriodNav.vue'
import TaskRow from '@/components/TaskRow.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useLoader } from '@/composables/useLoader'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import type { Occurrence } from '@/types/api'
import { formatDayName, formatShortDate, formatWeekRange, toISODate, weekDays } from '@/utils/dates'

const planner = usePlannerStore()
const categories = useCategoriesStore()
const { anchor, setAnchor } = useAnchorDate()

const days = computed(() => weekDays(anchor.value))
const from = computed(() => toISODate(days.value[0]!))
const to = computed(() => toISODate(days.value[6]!))

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
</script>

<template>
  <section aria-label="Semana">
    <PeriodNav
      :title="formatWeekRange(anchor)"
      prev-label="Semana anterior"
      next-label="Semana siguiente"
      @prev="setAnchor(addDays(anchor, -7))"
      @next="setAnchor(addDays(anchor, 7))"
    />

    <ul v-if="categories.items.length" class="mt-4 flex flex-wrap gap-2" aria-label="Categorías">
      <li v-for="category in categories.items" :key="category.id">
        <CategoryChip :name="category.name" :color="category.color" />
      </li>
    </ul>

    <p v-if="error" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

    <div class="mt-4 space-y-3" :aria-busy="loading">
      <article
        v-for="day in days"
        :key="toISODate(day)"
        class="rounded-2xl border border-line bg-white p-3.5"
        :aria-label="`${formatDayName(day)} ${formatShortDate(day)}`"
      >
        <header class="mb-2.5 flex items-baseline justify-between">
          <h3 class="font-semibold" :class="{ 'text-primary': isToday(day) }">{{ formatDayName(day) }}</h3>
          <span class="text-[13px] text-muted">{{ formatShortDate(day) }}</span>
        </header>

        <ul v-if="byDay.get(toISODate(day))?.length" class="space-y-2">
          <li v-for="occurrence in byDay.get(toISODate(day))" :key="occurrence.task_id + occurrence.date">
            <TaskRow
              :occurrence="occurrence"
              :color="categories.colorFor(occurrence.category_id)"
              @select="planner.openEdit"
            />
          </li>
        </ul>
        <button
          v-else
          type="button"
          class="min-h-11 w-full rounded-lg border border-dashed border-line text-sm text-muted transition hover:border-primary hover:text-primary"
          @click="planner.openCreate(toISODate(day))"
        >
          {{ loading ? 'Cargando…' : 'Sin tareas · Añadir' }}
        </button>
      </article>
    </div>
  </section>
</template>

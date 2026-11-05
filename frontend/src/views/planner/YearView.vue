<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { isSameMonth, isToday } from 'date-fns'
import PeriodNav from '@/components/PeriodNav.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useLoader } from '@/composables/useLoader'
import { usePlannerStore } from '@/stores/planner'
import type { DayStats } from '@/types/api'
import { WEEKDAY_INITIALS, formatMonthName, monthGrid, toISODate } from '@/utils/dates'

const router = useRouter()
const planner = usePlannerStore()
const { anchor, isCurrentPeriod, shift, goToday } = useAnchorDate()

const year = computed(() => anchor.value.getFullYear())

const { data: stats, loading, error } = useLoader(
  [year, () => planner.revision],
  () => planner.fetchDayStats(year.value),
  {} as DayStats,
)

const months = computed(() =>
  Array.from({ length: 12 }, (_, month) => {
    const date = new Date(year.value, month, 1)
    return { date, name: formatMonthName(date), weeks: monthGrid(date) }
  }),
)

/** Intensidad del mapa de calor: 0, 1–2 o 3+ tareas */
function heatClass(day: Date): string {
  if (isToday(day)) return 'bg-primary font-semibold text-on-primary'
  const count = stats.value[toISODate(day)] ?? 0
  if (count >= 3) return 'bg-heat-2 font-semibold'
  if (count >= 1) return 'bg-heat-1'
  return ''
}

function openMonth(date: Date): void {
  router.push({ name: 'month', query: { d: toISODate(date) } })
}
</script>

<template>
  <section aria-label="Año">
    <PeriodNav
      :title="String(year)"
      prev-label="Año anterior"
      next-label="Año siguiente"
      :show-today="!isCurrentPeriod"
      @prev="shift(-1)"
      @next="shift(1)"
      @today="goToday"
    />

    <p v-if="error" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

    <ol class="mt-4 grid grid-cols-3 gap-2 md:grid-cols-4 md:gap-3 lg:grid-cols-6" :aria-busy="loading">
      <li v-for="month in months" :key="month.name">
        <button
          type="button"
          class="flex h-full w-full flex-col justify-start rounded-xl border bg-card p-1.5 text-left transition hover:border-primary md:p-2.5"
          :class="isSameMonth(month.date, new Date()) ? 'border-2 border-primary' : 'border-line'"
          :aria-label="`${month.name}: ver mes`"
          @click="openMonth(month.date)"
        >
          <span
            class="mb-1 block px-0.5 text-xs font-semibold md:mb-1.5 md:text-sm"
            :class="{ 'text-primary': isSameMonth(month.date, new Date()) }"
          >
            {{ month.name }}
          </span>
          <span
            class="grid grid-cols-7 gap-px text-center text-[8px] leading-[13px] md:gap-0.5 md:text-[10px] md:leading-[18px]"
            aria-hidden="true"
          >
            <span v-for="initial in WEEKDAY_INITIALS" :key="initial" class="text-muted/70">{{ initial }}</span>
            <template v-for="(week, index) in month.weeks" :key="index">
              <span v-for="(day, dayIndex) in week" :key="dayIndex" class="rounded-[3px]" :class="day ? heatClass(day) : ''">
                {{ day?.getDate() ?? '' }}
              </span>
            </template>
          </span>
        </button>
      </li>
    </ol>

    <div class="mt-3 flex items-center gap-1.5 text-[11px] text-muted md:mt-4 md:text-xs" aria-hidden="true">
      <span>Menos</span>
      <span class="size-3 rounded-[3px] border border-line bg-card" />
      <span class="size-3 rounded-[3px] bg-heat-1" />
      <span class="size-3 rounded-[3px] bg-heat-2" />
      <span>Más tareas</span>
    </div>
  </section>
</template>

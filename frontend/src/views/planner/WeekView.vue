<script setup lang="ts">
import { computed } from 'vue'
import { isToday } from 'date-fns'
import CategoryChip from '@/components/CategoryChip.vue'
import PeriodNav from '@/components/PeriodNav.vue'
import TaskRow from '@/components/TaskRow.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useOccurrences } from '@/composables/useOccurrences'
import { useTaskActions } from '@/composables/useTaskActions'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import { formatDayName, formatShortDate, formatWeekRange, toISODate, weekDays } from '@/utils/dates'

const planner = usePlannerStore()
const categories = useCategoriesStore()
const { anchor, isCurrentPeriod, shift, goToday } = useAnchorDate()
const { onDragStart, onToggle, isDragging, isDropTarget } = useTaskActions()

const days = computed(() => weekDays(anchor.value))
const from = computed(() => toISODate(days.value[0]!))
const to = computed(() => toISODate(days.value[6]!))

const { byDay, loading, error } = useOccurrences(from, to)
</script>

<template>
  <section aria-label="Semana">
    <PeriodNav
      :title="formatWeekRange(anchor)"
      prev-label="Semana anterior"
      next-label="Semana siguiente"
      :show-today="!isCurrentPeriod"
      @prev="shift(-1)"
      @next="shift(1)"
      @today="goToday"
    />

    <ul v-if="categories.items.length" class="mt-4 flex flex-wrap gap-2" aria-label="Categorías">
      <li v-for="category in categories.items" :key="category.id">
        <CategoryChip :name="category.name" :color="category.color" />
      </li>
    </ul>

    <p v-if="error" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

    <!-- Móvil: una tarjeta por día. Tablet: 2 columnas. Ordenador: los 7 días en columnas -->
    <div
      class="mt-4 space-y-3 md:grid md:grid-cols-2 md:items-start md:gap-3 md:space-y-0 lg:grid-cols-7 lg:items-stretch lg:gap-2"
      :aria-busy="loading"
    >
      <!-- Cada día es un destino para soltar tareas -->
      <article
        v-for="day in days"
        :key="toISODate(day)"
        :data-drop-date="toISODate(day)"
        class="rounded-2xl border bg-white p-3.5 transition-colors lg:flex lg:min-h-64 lg:flex-col lg:p-2.5"
        :class="
          isDropTarget(toISODate(day))
            ? 'border-primary bg-primary-soft/60 ring-2 ring-primary/30'
            : isToday(day)
              ? 'border-line lg:border-primary/50'
              : 'border-line'
        "
        :aria-label="`${formatDayName(day)} ${formatShortDate(day)}`"
      >
        <header class="mb-2.5 flex items-baseline justify-between lg:mb-2 lg:flex-col lg:items-start lg:px-0.5">
          <h3 class="font-semibold" :class="{ 'text-primary': isToday(day) }">{{ formatDayName(day) }}</h3>
          <span class="text-[13px] text-muted">{{ formatShortDate(day) }}</span>
        </header>

        <ul v-if="byDay.get(toISODate(day))?.length" class="space-y-2 lg:space-y-1.5">
          <li v-for="occurrence in byDay.get(toISODate(day))" :key="occurrence.task_id + occurrence.original_date">
            <TaskRow
              :occurrence="occurrence"
              :color="categories.colorFor(occurrence.category_id)"
              stack-on-desktop
              draggable
              :dragging="isDragging(occurrence)"
              @select="planner.openEdit"
              @toggle="onToggle"
              @drag-start="(event, element) => onDragStart(occurrence, event, element)"
            />
          </li>
        </ul>
        <button
          v-else
          type="button"
          class="min-h-11 w-full rounded-lg border border-dashed border-line text-sm text-muted transition hover:border-primary hover:text-primary lg:flex-1"
          @click="planner.openCreate(toISODate(day))"
        >
          <template v-if="loading">Cargando…</template>
          <template v-else><span class="lg:hidden">Sin tareas · </span>Añadir</template>
        </button>
      </article>
    </div>
  </section>
</template>

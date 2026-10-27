import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { addDays, addMonths, addYears, isSameMonth, isSameWeek, isSameYear, isValid } from 'date-fns'
import type { ViewName } from '@/types/api'
import { fromISODate, toISODate } from '@/utils/dates'

function today(): Date {
  return fromISODate(toISODate(new Date()))
}

/**
 * Día de referencia de las vistas del planificador, guardado en la URL (?d=2026-10-15)
 * para que recargar o compartir el enlace mantenga la semana / mes / año.
 */
export function useAnchorDate() {
  const route = useRoute()
  const router = useRouter()

  const view = computed<ViewName>(() => (route.name === 'month' || route.name === 'year' ? route.name : 'week'))

  const anchor = computed<Date>(() => {
    const value = route.query.d
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
      const date = fromISODate(value)
      if (isValid(date)) return date
    }
    return today()
  })

  /** ¿El periodo que se ve contiene el día de hoy? */
  const isCurrentPeriod = computed(() => {
    const now = today()
    if (view.value === 'week') return isSameWeek(anchor.value, now, { weekStartsOn: 1 })
    if (view.value === 'month') return isSameMonth(anchor.value, now)
    return isSameYear(anchor.value, now)
  })

  function setAnchor(date: Date): void {
    router.replace({ query: { ...route.query, d: toISODate(date) } })
  }

  /** Periodo anterior (-1) o siguiente (+1) de la vista actual */
  function shift(direction: 1 | -1): void {
    const next = {
      week: () => addDays(anchor.value, 7 * direction),
      month: () => addMonths(anchor.value, direction),
      year: () => addYears(anchor.value, direction),
    }
    setAnchor(next[view.value]())
  }

  function goToday(): void {
    setAnchor(today())
  }

  return { view, anchor, isCurrentPeriod, setAnchor, shift, goToday }
}

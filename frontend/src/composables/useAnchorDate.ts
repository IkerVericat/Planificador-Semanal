import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { isValid } from 'date-fns'
import { fromISODate, toISODate } from '@/utils/dates'

/**
 * Día de referencia de las vistas del planificador, guardado en la URL (?d=2026-10-15)
 * para que recargar o compartir el enlace mantenga la semana / mes / año.
 */
export function useAnchorDate() {
  const route = useRoute()
  const router = useRouter()

  const anchor = computed<Date>(() => {
    const value = route.query.d
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
      const date = fromISODate(value)
      if (isValid(date)) return date
    }
    return fromISODate(toISODate(new Date()))
  })

  function setAnchor(date: Date): void {
    router.replace({ query: { ...route.query, d: toISODate(date) } })
  }

  return { anchor, setAnchor }
}

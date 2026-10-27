import { computed, type Ref } from 'vue'
import { usePlannerStore } from '@/stores/planner'
import type { Occurrence } from '@/types/api'
import { groupByDate } from '@/utils/occurrences'
import { useLoader } from './useLoader'

/**
 * Ocurrencias entre `from` y `to` con los cambios optimistas aplicados.
 * Se recargan al cambiar el rango o al modificar cualquier tarea.
 */
export function useOccurrences(from: Ref<string>, to: Ref<string>) {
  const planner = usePlannerStore()

  const { data, loading, error } = useLoader(
    [from, to, () => planner.revision],
    () => planner.fetchOccurrences(from.value, to.value),
    [] as Occurrence[],
  )

  const occurrences = computed(() => planner.applyPatches(data.value))
  const byDay = computed(() => groupByDate(occurrences.value))

  return { occurrences, byDay, loading, error }
}

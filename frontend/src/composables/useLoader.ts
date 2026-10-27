import { ref, shallowRef, watch, type WatchSource } from 'vue'
import { ApiError } from '@/api'

/**
 * Vuelve a pedir datos cada vez que cambian las fuentes observadas.
 * Mantiene los datos anteriores mientras llega la respuesta (sin parpadeos)
 * e ignora las respuestas que llegan tarde.
 */
export function useLoader<T>(sources: WatchSource[], fetcher: () => Promise<T>, initial: T) {
  const data = shallowRef<T>(initial)
  const loading = ref(false)
  const error = ref<string | null>(null)
  let requestId = 0

  async function run(): Promise<void> {
    const id = ++requestId
    loading.value = true
    error.value = null
    try {
      const result = await fetcher()
      if (id === requestId) data.value = result
    } catch (e) {
      if (id === requestId) {
        error.value = e instanceof ApiError ? e.message : 'No se han podido cargar las tareas.'
      }
    } finally {
      if (id === requestId) loading.value = false
    }
  }

  watch(sources, run, { immediate: true })

  return { data, loading, error, reload: run }
}

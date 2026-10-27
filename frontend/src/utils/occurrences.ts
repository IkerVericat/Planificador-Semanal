import type { Occurrence } from '@/types/api'

/** Clave única de una ocurrencia: la tarea y su día original (no cambia al moverla) */
export function occurrenceKey(occurrence: Pick<Occurrence, 'task_id' | 'original_date'>): string {
  return `${occurrence.task_id}|${occurrence.original_date}`
}

/** Mismo orden que la API: por fecha, las de todo el día primero, luego por hora y título */
export function compareOccurrences(a: Occurrence, b: Occurrence): number {
  if (a.date !== b.date) return a.date < b.date ? -1 : 1
  if ((a.start_time === null) !== (b.start_time === null)) return a.start_time === null ? -1 : 1
  if (a.start_time !== b.start_time) return (a.start_time ?? '') < (b.start_time ?? '') ? -1 : 1
  return a.title.localeCompare(b.title, 'es')
}

/** Agrupa por el día en el que se muestran */
export function groupByDate(occurrences: Occurrence[]): Map<string, Occurrence[]> {
  const groups = new Map<string, Occurrence[]>()
  for (const occurrence of occurrences) {
    groups.set(occurrence.date, [...(groups.get(occurrence.date) ?? []), occurrence])
  }
  return groups
}

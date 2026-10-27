import { reactive, ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, occurrencesApi, statsApi, tasksApi } from '@/api'
import type { DayStats, Occurrence, Task, TaskPayload } from '@/types/api'
import { formatDayLong, fromISODate } from '@/utils/dates'
import { compareOccurrences, occurrenceKey } from '@/utils/occurrences'

/** Qué muestra la hoja inferior de tareas */
export type TaskEditor = { mode: 'create'; date: string } | { mode: 'edit'; occurrence: Occurrence }

/** Aviso inferior; si lleva `undo` muestra el botón "Deshacer" */
export interface Toast {
  id: number
  message: string
  undo?: () => Promise<void>
}

/**
 * Cambio optimista: se ve al instante mientras la API responde y se descarta
 * cuando llegan datos nuevos del servidor (o se revierte si la llamada falla).
 */
interface Patch {
  date?: string
  is_completed?: boolean
  hidden?: boolean
  /** Valor de `revision` cuando se creó */
  revision: number
}

/** Clave de parche que afecta a todas las ocurrencias de una tarea (al borrar la serie) */
function wholeTaskKey(taskId: string): string {
  return `${taskId}|*`
}

function payloadFrom(task: Task): TaskPayload {
  return {
    title: task.title,
    notes: task.notes,
    task_date: task.task_date,
    start_time: task.start_time,
    end_time: task.end_time,
    category_id: task.category_id,
    is_completed: task.is_completed,
    recurrence: task.recurrence,
    recurrence_days: task.recurrence_days,
    recurrence_until: task.recurrence_until,
  }
}

export const usePlannerStore = defineStore('planner', () => {
  /** Sube cada vez que cambian las tareas; las vistas la observan para recargar */
  const revision = ref(0)
  const editor = ref<TaskEditor | null>(null)
  const toast = ref<Toast | null>(null)
  const patches = reactive(new Map<string, Patch>())
  let toastSeq = 0

  // --- Hoja de tareas ---

  function openCreate(date: string): void {
    editor.value = { mode: 'create', date }
  }

  function openEdit(occurrence: Occurrence): void {
    editor.value = { mode: 'edit', occurrence }
  }

  function closeEditor(): void {
    editor.value = null
  }

  // --- Avisos ---

  function notify(message: string, undo?: () => Promise<void>): void {
    toast.value = { id: ++toastSeq, message, undo }
  }

  function notifyError(error: unknown): void {
    notify(error instanceof ApiError ? error.message : 'Algo ha fallado. Inténtalo de nuevo.')
  }

  function dismissToast(id: number): void {
    if (toast.value?.id === id) toast.value = null
  }

  const undoing = ref(false)

  /** Ejecuta el "Deshacer" del aviso visible (botón o Ctrl/Cmd+Z) */
  async function undoToast(): Promise<void> {
    const current = toast.value
    if (!current?.undo || undoing.value) return
    undoing.value = true
    try {
      await current.undo()
      dismissToast(current.id)
    } catch (error) {
      notifyError(error)
    } finally {
      undoing.value = false
    }
  }

  // --- Lectura ---

  async function fetchOccurrences(from: string, to: string): Promise<Occurrence[]> {
    const startedAt = revision.value
    const list = await occurrencesApi.list(from, to)
    // Los datos recibidos ya incluyen los cambios hechos antes de pedirlos
    for (const [key, patch] of patches) {
      if (patch.revision < startedAt) patches.delete(key)
    }
    return list
  }

  function fetchDayStats(year: number): Promise<DayStats> {
    return statsApi.days(year)
  }

  /** Aplica los cambios optimistas pendientes a una lista de ocurrencias */
  function applyPatches(list: Occurrence[]): Occurrence[] {
    if (patches.size === 0) return list
    return list
      .flatMap((occurrence) => {
        const patch = { ...patches.get(wholeTaskKey(occurrence.task_id)), ...patches.get(occurrenceKey(occurrence)) }
        if (patch.hidden) return []
        return [
          {
            ...occurrence,
            date: patch.date ?? occurrence.date,
            is_completed: patch.is_completed ?? occurrence.is_completed,
          },
        ]
      })
      .sort(compareOccurrences)
  }

  // --- Escritura ---

  async function withPatch(key: string, patch: Omit<Patch, 'revision'>, action: () => Promise<unknown>): Promise<void> {
    patches.set(key, { ...patches.get(key), ...patch, revision: revision.value })
    try {
      await action()
      revision.value++
    } catch (error) {
      patches.delete(key)
      throw error
    }
  }

  async function createTask(payload: TaskPayload): Promise<void> {
    await tasksApi.create(payload)
    revision.value++
  }

  async function updateTask(id: string, payload: TaskPayload): Promise<void> {
    await tasksApi.update(id, payload)
    revision.value++
  }

  /**
   * Cambia de día una ocurrencia (arrastrar y soltar). En una tarea recurrente solo se mueve
   * ese día; en una normal cambia su fecha.
   */
  async function moveOccurrence(occurrence: Occurrence, date: string, undoable = true): Promise<void> {
    if (date === occurrence.date) return
    const previousDate = occurrence.date

    await withPatch(occurrenceKey(occurrence), { date }, () =>
      occurrence.is_recurring
        ? tasksApi.updateOccurrence(occurrence.task_id, occurrence.original_date, {
            moved_to_date: date === occurrence.original_date ? null : date,
          })
        : tasksApi.update(occurrence.task_id, { task_date: date }),
    )

    if (undoable) {
      // En una tarea normal la fecha original es la propia fecha de la tarea
      const moved = { ...occurrence, date, original_date: occurrence.is_recurring ? occurrence.original_date : date }
      notify(`Movida al ${formatDayLong(fromISODate(date)).toLowerCase()}`, () =>
        moveOccurrence(moved, previousDate, false),
      )
    }
  }

  /** Marca hecha / pendiente: en una recurrente solo ese día, en una normal la tarea */
  async function toggleCompleted(occurrence: Occurrence, undoable = true): Promise<void> {
    const is_completed = !occurrence.is_completed

    await withPatch(occurrenceKey(occurrence), { is_completed }, () =>
      occurrence.is_recurring
        ? tasksApi.updateOccurrence(occurrence.task_id, occurrence.original_date, { is_completed })
        : tasksApi.update(occurrence.task_id, { is_completed }),
    )

    if (undoable) {
      notify(is_completed ? 'Tarea completada' : 'Tarea pendiente', () =>
        toggleCompleted({ ...occurrence, is_completed }, false),
      )
    }
  }

  /** Quita solo ese día de una tarea recurrente */
  async function skipOccurrence(occurrence: Occurrence): Promise<void> {
    await withPatch(occurrenceKey(occurrence), { hidden: true }, () =>
      tasksApi.updateOccurrence(occurrence.task_id, occurrence.original_date, { is_skipped: true }),
    )

    notify('Día quitado de la repetición', async () => {
      await tasksApi.updateOccurrence(occurrence.task_id, occurrence.original_date, { is_skipped: false })
      revision.value++
    })
  }

  /**
   * Borra la tarea (y todas sus repeticiones). Deshacer la vuelve a crear con los mismos datos;
   * los cambios hechos a días sueltos (completar, mover) no se recuperan.
   */
  async function deleteTask(task: Task): Promise<void> {
    await withPatch(wholeTaskKey(task.id), { hidden: true }, () => tasksApi.remove(task.id))

    notify('Tarea eliminada', () => createTask(payloadFrom(task)))
  }

  function reset(): void {
    editor.value = null
    toast.value = null
    patches.clear()
    revision.value++
  }

  return {
    revision,
    editor,
    toast,
    undoing,
    openCreate,
    openEdit,
    closeEditor,
    notify,
    notifyError,
    dismissToast,
    undoToast,
    fetchOccurrences,
    fetchDayStats,
    applyPatches,
    createTask,
    updateTask,
    moveOccurrence,
    toggleCompleted,
    skipOccurrence,
    deleteTask,
    reset,
  }
})

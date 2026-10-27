import { ref } from 'vue'
import { defineStore } from 'pinia'
import { occurrencesApi, statsApi, tasksApi } from '@/api'
import type { DayStats, Occurrence, OccurrencePayload, TaskPayload } from '@/types/api'

/** Qué muestra la hoja inferior de tareas */
export type TaskEditor = { mode: 'create'; date: string } | { mode: 'edit'; occurrence: Occurrence }

export const usePlannerStore = defineStore('planner', () => {
  /** Sube cada vez que cambian las tareas; las vistas la observan para recargar */
  const revision = ref(0)
  const editor = ref<TaskEditor | null>(null)

  function openCreate(date: string): void {
    editor.value = { mode: 'create', date }
  }

  function openEdit(occurrence: Occurrence): void {
    editor.value = { mode: 'edit', occurrence }
  }

  function closeEditor(): void {
    editor.value = null
  }

  function fetchOccurrences(from: string, to: string): Promise<Occurrence[]> {
    return occurrencesApi.list(from, to)
  }

  function fetchDayStats(year: number): Promise<DayStats> {
    return statsApi.days(year)
  }

  async function createTask(payload: TaskPayload): Promise<void> {
    await tasksApi.create(payload)
    revision.value++
  }

  async function updateTask(id: string, payload: TaskPayload): Promise<void> {
    await tasksApi.update(id, payload)
    revision.value++
  }

  async function deleteTask(id: string): Promise<void> {
    await tasksApi.remove(id)
    revision.value++
  }

  /** Completar o saltar un solo día de una tarea recurrente */
  async function updateOccurrence(occurrence: Occurrence, payload: OccurrencePayload): Promise<void> {
    await tasksApi.updateOccurrence(occurrence.task_id, occurrence.date, payload)
    revision.value++
  }

  /** Marca hecha / pendiente: en una recurrente solo ese día, en una normal la tarea */
  function toggleCompleted(occurrence: Occurrence): Promise<void> {
    const is_completed = !occurrence.is_completed
    return occurrence.is_recurring
      ? updateOccurrence(occurrence, { is_completed })
      : updateTask(occurrence.task_id, { is_completed })
  }

  function reset(): void {
    editor.value = null
    revision.value++
  }

  return {
    revision,
    editor,
    openCreate,
    openEdit,
    closeEditor,
    fetchOccurrences,
    fetchDayStats,
    createTask,
    updateTask,
    deleteTask,
    updateOccurrence,
    toggleCompleted,
    reset,
  }
})

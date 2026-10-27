import { usePlannerStore } from '@/stores/planner'
import type { Occurrence } from '@/types/api'
import { occurrenceKey } from '@/utils/occurrences'
import { dragState, startTaskDrag } from './useTaskDrag'

/** Acciones rápidas sobre una tarea desde las vistas (arrastrar, completar) con aviso si fallan */
export function useTaskActions() {
  const planner = usePlannerStore()

  function moveTo(occurrence: Occurrence, date: string): void {
    planner.moveOccurrence(occurrence, date).catch(planner.notifyError)
  }

  function onDragStart(occurrence: Occurrence, event: PointerEvent, element: HTMLElement): void {
    startTaskDrag(event, occurrence, element, moveTo)
  }

  function onToggle(occurrence: Occurrence): void {
    planner.toggleCompleted(occurrence).catch(planner.notifyError)
  }

  function isDragging(occurrence: Occurrence): boolean {
    return dragState.occurrence !== null && occurrenceKey(dragState.occurrence) === occurrenceKey(occurrence)
  }

  /** ¿Se está arrastrando una tarea sobre este día? */
  function isDropTarget(date: string): boolean {
    return dragState.occurrence !== null && dragState.overDate === date && dragState.occurrence.date !== date
  }

  return { onDragStart, onToggle, isDragging, isDropTarget }
}

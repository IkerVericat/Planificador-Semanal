import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { tasksApi } from '@/api'
import type { Occurrence } from '@/types/api'
import { usePlannerStore } from '../planner'

vi.mock('@/api', async (importOriginal) => {
  const original = await importOriginal<typeof import('@/api')>()
  return {
    ...original,
    tasksApi: {
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      updateOccurrence: vi.fn(),
    },
  }
})

function occurrence(overrides: Partial<Occurrence> = {}): Occurrence {
  return {
    task_id: 't1',
    date: '2026-10-12',
    original_date: '2026-10-12',
    start_time: '09:00',
    end_time: null,
    title: 'Curso',
    notes: null,
    category_id: null,
    is_completed: false,
    is_recurring: false,
    ...overrides,
  }
}

describe('planner store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('mover una tarea normal cambia su fecha y se ve al instante', async () => {
    const planner = usePlannerStore()
    let resolve: () => void = () => {}
    vi.mocked(tasksApi.update).mockReturnValue(new Promise((r) => (resolve = () => r({} as never))))

    const moving = planner.moveOccurrence(occurrence(), '2026-10-14')

    // Antes de que responda la API ya aparece en el nuevo día
    expect(planner.applyPatches([occurrence()])[0]?.date).toBe('2026-10-14')
    resolve()
    await moving

    expect(tasksApi.update).toHaveBeenCalledWith('t1', { task_date: '2026-10-14' })
    expect(planner.toast?.message).toBe('Movida al miércoles 14 de octubre')
  })

  it('mover una recurrente solo mueve ese día, usando su fecha original', async () => {
    const planner = usePlannerStore()
    const item = occurrence({ is_recurring: true, date: '2026-10-13', original_date: '2026-10-12' })

    await planner.moveOccurrence(item, '2026-10-15')
    expect(tasksApi.updateOccurrence).toHaveBeenLastCalledWith('t1', '2026-10-12', { moved_to_date: '2026-10-15' })

    // Volver a su día original quita el movimiento
    await planner.moveOccurrence({ ...item, date: '2026-10-15' }, '2026-10-12')
    expect(tasksApi.updateOccurrence).toHaveBeenLastCalledWith('t1', '2026-10-12', { moved_to_date: null })
  })

  it('deshacer devuelve la tarea a su día', async () => {
    const planner = usePlannerStore()

    await planner.moveOccurrence(occurrence(), '2026-10-14')
    await planner.undoToast()

    expect(tasksApi.update).toHaveBeenLastCalledWith('t1', { task_date: '2026-10-12' })
    expect(planner.toast).toBeNull()
  })

  it('completar con un toque y deshacer', async () => {
    const planner = usePlannerStore()
    const item = occurrence({ is_recurring: true })

    await planner.toggleCompleted(item)
    expect(tasksApi.updateOccurrence).toHaveBeenLastCalledWith('t1', '2026-10-12', { is_completed: true })
    expect(planner.toast?.message).toBe('Tarea completada')

    await planner.undoToast()
    expect(tasksApi.updateOccurrence).toHaveBeenLastCalledWith('t1', '2026-10-12', { is_completed: false })
  })

  it('si la API falla, el cambio optimista se revierte', async () => {
    const planner = usePlannerStore()
    vi.mocked(tasksApi.update).mockRejectedValue(new Error('caída'))

    await expect(planner.toggleCompleted(occurrence())).rejects.toThrow('caída')

    expect(planner.applyPatches([occurrence()])[0]?.is_completed).toBe(false)
    expect(planner.toast).toBeNull()
  })
})

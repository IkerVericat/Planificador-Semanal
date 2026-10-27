import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { tasksApi } from '@/api'
import TaskForm from '../TaskForm.vue'

vi.mock('@/api', async (importOriginal) => {
  const original = await importOriginal<typeof import('@/api')>()
  return {
    ...original,
    tasksApi: { get: vi.fn(), create: vi.fn(), update: vi.fn(), remove: vi.fn(), updateOccurrence: vi.fn() },
  }
})

// 15 de octubre de 2026 es jueves
function mountCreateForm() {
  return mount(TaskForm, { props: { editor: { mode: 'create', date: '2026-10-15' } } })
}

const day = (wrapper: ReturnType<typeof mountCreateForm>, name: string) => wrapper.find(`[aria-label="${name}"]`)
const recurrence = (wrapper: ReturnType<typeof mountCreateForm>) =>
  (wrapper.find('#task-recurrence').element as HTMLSelectElement).value

describe('TaskForm · días de la semana', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('al crear una tarea se ven los días sin elegir antes "Cada semana"', () => {
    const wrapper = mountCreateForm()

    expect(wrapper.findAll('fieldset [aria-pressed]')).toHaveLength(7)
    expect(wrapper.findAll('[aria-pressed="true"]')).toHaveLength(0)
    expect(recurrence(wrapper)).toBe('none')
  })

  it('marcar días la convierte en semanal y se guardan esos días', async () => {
    const wrapper = mountCreateForm()

    await day(wrapper, 'lunes').trigger('click')
    await day(wrapper, 'miércoles').trigger('click')

    expect(recurrence(wrapper)).toBe('weekly')
    expect(wrapper.text()).toContain('Se repite cada lunes y miércoles.')

    await wrapper.find('#task-title').setValue('Gimnasio')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(tasksApi.create).toHaveBeenCalledWith(
      expect.objectContaining({ title: 'Gimnasio', recurrence: 'weekly', recurrence_days: [1, 3] }),
    )
  })

  it('quitar todos los días hace que deje de repetirse', async () => {
    const wrapper = mountCreateForm()

    await day(wrapper, 'viernes').trigger('click')
    await day(wrapper, 'viernes').trigger('click')

    expect(recurrence(wrapper)).toBe('none')
  })

  it('con los 7 días pasa a "Cada día"', async () => {
    const wrapper = mountCreateForm()

    for (const name of ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']) {
      await day(wrapper, name).trigger('click')
    }

    expect(recurrence(wrapper)).toBe('daily')
    expect(wrapper.text()).toContain('Se repite todos los días.')
  })

  it('elegir "Cada semana" en el desplegable marca el día de la fecha', async () => {
    const wrapper = mountCreateForm()

    await wrapper.find('#task-recurrence').setValue('weekly')

    expect(day(wrapper, 'jueves').attributes('aria-pressed')).toBe('true')
  })
})

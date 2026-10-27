import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TaskRow from '../TaskRow.vue'
import type { Occurrence } from '@/types/api'

function occurrence(overrides: Partial<Occurrence> = {}): Occurrence {
  return {
    task_id: 'a1',
    date: '2026-10-12',
    original_date: '2026-10-12',
    start_time: '19:30',
    end_time: null,
    title: 'Limpiar alacena',
    notes: null,
    category_id: null,
    is_completed: false,
    is_recurring: false,
    ...overrides,
  }
}

describe('TaskRow', () => {
  it('muestra la hora en 12 h y el título con el color de la categoría', () => {
    const wrapper = mount(TaskRow, { props: { occurrence: occurrence(), color: '#8DC63F' } })

    expect(wrapper.text()).toContain('7:30 PM')
    expect(wrapper.text()).toContain('Limpiar alacena')
    // Los dos bloques pintan su fondo con la variable --category
    expect(wrapper.attributes('style')).toContain('--category: #8DC63F')
    const blocks = wrapper.findAll('[data-task-main] > span')
    expect(blocks).toHaveLength(2)
    for (const block of blocks) {
      expect(block.classes()).toContain('bg-(--category)')
    }
  })

  it('las tareas sin hora son de todo el día', () => {
    const wrapper = mount(TaskRow, { props: { occurrence: occurrence({ start_time: null }), color: '#fff' } })

    expect(wrapper.text()).toContain('Todo el día')
  })

  it('en la vista Semana se apila en ordenador', () => {
    const stacked = mount(TaskRow, { props: { occurrence: occurrence(), color: '#fff', stackOnDesktop: true } })
    const normal = mount(TaskRow, { props: { occurrence: occurrence(), color: '#fff' } })

    expect(stacked.find('[data-task-main]').classes()).toContain('lg:flex-col')
    expect(normal.find('[data-task-main]').classes()).not.toContain('lg:flex-col')
  })

  it('emite select al tocarla y describe su estado', async () => {
    const item = occurrence({ is_completed: true, is_recurring: true })
    const wrapper = mount(TaskRow, { props: { occurrence: item, color: '#fff' } })

    await wrapper.find('[data-task-main]').trigger('click')

    expect(wrapper.emitted('select')).toEqual([[item]])
    expect(wrapper.find('[data-task-main]').attributes('aria-label')).toBe(
      '7:30 PM, Limpiar alacena, hecha, se repite',
    )
  })

  it('el círculo la completa sin abrir el formulario', async () => {
    const item = occurrence()
    const wrapper = mount(TaskRow, { props: { occurrence: item, color: '#fff' } })
    const toggle = wrapper.find('[data-task-toggle]')

    expect(toggle.attributes('aria-pressed')).toBe('false')
    await toggle.trigger('click')

    expect(wrapper.emitted('toggle')).toEqual([[item]])
    expect(wrapper.emitted('select')).toBeUndefined()
  })

  it('solo empieza a arrastrar si es arrastrable y no desde el círculo', async () => {
    const fixed = mount(TaskRow, { props: { occurrence: occurrence(), color: '#fff' } })
    await fixed.find('[data-task-main]').trigger('pointerdown')
    expect(fixed.emitted('dragStart')).toBeUndefined()

    const draggable = mount(TaskRow, { props: { occurrence: occurrence(), color: '#fff', draggable: true } })
    await draggable.find('[data-task-toggle]').trigger('pointerdown')
    expect(draggable.emitted('dragStart')).toBeUndefined()

    await draggable.find('[data-task-main]').trigger('pointerdown')
    expect(draggable.emitted('dragStart')).toHaveLength(1)
  })
})

import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TaskRow from '../TaskRow.vue'
import type { Occurrence } from '@/types/api'

function occurrence(overrides: Partial<Occurrence> = {}): Occurrence {
  return {
    task_id: 'a1',
    date: '2026-10-12',
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
    const blocks = wrapper.findAll('button > span')
    expect(blocks).toHaveLength(2)
    for (const block of blocks) {
      expect(block.attributes('style')).toContain('background-color: rgb(141, 198, 63)')
    }
  })

  it('las tareas sin hora son de todo el día', () => {
    const wrapper = mount(TaskRow, { props: { occurrence: occurrence({ start_time: null }), color: '#fff' } })

    expect(wrapper.text()).toContain('Todo el día')
  })

  it('emite select al tocarla y describe su estado', async () => {
    const item = occurrence({ is_completed: true, is_recurring: true })
    const wrapper = mount(TaskRow, { props: { occurrence: item, color: '#fff' } })

    await wrapper.trigger('click')

    expect(wrapper.emitted('select')).toEqual([[item]])
    expect(wrapper.attributes('aria-label')).toBe('7:30 PM, Limpiar alacena, hecha, se repite')
  })
})

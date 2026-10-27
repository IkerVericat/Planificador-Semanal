import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import SegmentedControl from '../SegmentedControl.vue'

const options = [
  { value: 'week', label: 'Semana' },
  { value: 'month', label: 'Mes' },
  { value: 'year', label: 'Año' },
]

describe('SegmentedControl', () => {
  it('marca la opción activa y emite la elegida', async () => {
    const wrapper = mount(SegmentedControl, {
      props: { options, label: 'Vista', modelValue: 'week' },
    })

    const buttons = wrapper.findAll('[role="radio"]')
    expect(buttons.map((b) => b.attributes('aria-checked'))).toEqual(['true', 'false', 'false'])

    await buttons[2]!.trigger('click')

    expect(wrapper.emitted('update:modelValue')).toEqual([['year']])
  })
})

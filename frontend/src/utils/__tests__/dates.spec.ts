import { describe, expect, it } from 'vitest'
import {
  formatDayLong,
  formatDayName,
  formatMonthYear,
  formatShortDate,
  formatTaskCount,
  formatTime12h,
  formatWeekRange,
  fromISODate,
  isoWeekday,
  monthGrid,
  toISODate,
  weekDays,
} from '../dates'

describe('formatTime12h', () => {
  it.each([
    ['09:00', '9 AM'],
    ['12:00', '12 PM'],
    ['19:00', '7 PM'],
    ['19:30', '7:30 PM'],
    ['00:15', '12:15 AM'],
    ['23:59', '11:59 PM'],
  ])('%s → %s', (time, expected) => {
    expect(formatTime12h(time)).toBe(expected)
  })
})

describe('semanas', () => {
  it('empiezan en lunes', () => {
    const days = weekDays(fromISODate('2026-10-15'))
    expect(days.map(toISODate)).toEqual([
      '2026-10-12',
      '2026-10-13',
      '2026-10-14',
      '2026-10-15',
      '2026-10-16',
      '2026-10-17',
      '2026-10-18',
    ])
  })

  it('el domingo pertenece a la semana anterior', () => {
    expect(toISODate(weekDays(fromISODate('2026-10-18'))[0]!)).toBe('2026-10-12')
  })

  it('formatea el rango como en el mockup', () => {
    expect(formatWeekRange(fromISODate('2026-10-15'))).toBe('12 – 18 de octubre')
    expect(formatWeekRange(fromISODate('2026-10-01'))).toBe('28 de septiembre – 4 de octubre')
    expect(formatWeekRange(fromISODate('2026-12-31'))).toBe(
      '28 de diciembre de 2026 – 3 de enero de 2027',
    )
  })

  it('isoWeekday: 1 = lunes … 7 = domingo', () => {
    expect(isoWeekday(fromISODate('2026-10-12'))).toBe(1)
    expect(isoWeekday(fromISODate('2026-10-18'))).toBe(7)
  })
})

describe('textos en español', () => {
  it('día, mes y fecha corta', () => {
    const day = fromISODate('2026-10-15')
    expect(formatDayLong(day)).toBe('Jueves 15 de octubre')
    expect(formatDayName(fromISODate('2026-10-12'))).toBe('Lunes')
    expect(formatMonthYear(day)).toBe('Octubre 2026')
    expect(formatShortDate(fromISODate('2026-10-12'))).toBe('12 oct')
  })

  it('número de tareas', () => {
    expect(formatTaskCount(1)).toBe('1 tarea')
    expect(formatTaskCount(4)).toBe('4 tareas')
  })
})

describe('monthGrid', () => {
  it('octubre de 2026 empieza en jueves y tiene 5 semanas', () => {
    const grid = monthGrid(fromISODate('2026-10-01'))
    expect(grid).toHaveLength(5)
    expect(grid[0]!.slice(0, 3)).toEqual([null, null, null])
    expect(toISODate(grid[0]![3]!)).toBe('2026-10-01')
    expect(toISODate(grid[4]![5]!)).toBe('2026-10-31')
    expect(grid[4]![6]).toBeNull()
  })
})

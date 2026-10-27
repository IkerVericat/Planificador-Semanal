import {
  addDays,
  eachDayOfInterval,
  endOfMonth,
  endOfWeek,
  format,
  getISODay,
  isSameMonth,
  isSameYear,
  parseISO,
  startOfMonth,
  startOfWeek,
} from 'date-fns'
import { es } from 'date-fns/locale'
import type { IsoWeekday } from '@/types/api'

// La semana empieza en lunes en toda la app
const WEEK_OPTIONS = { weekStartsOn: 1 } as const

/** Iniciales de la cabecera de los calendarios, de lunes a domingo */
export const WEEKDAY_INITIALS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'] as const

export function capitalize(text: string): string {
  return text.charAt(0).toLocaleUpperCase('es') + text.slice(1)
}

/** Date → "2026-10-15" (fecha local, sin zona horaria) */
export function toISODate(date: Date): string {
  return format(date, 'yyyy-MM-dd')
}

/** "2026-10-15" → Date a las 00:00 locales */
export function fromISODate(value: string): Date {
  return parseISO(value)
}

export function weekStart(date: Date): Date {
  return startOfWeek(date, WEEK_OPTIONS)
}

/** Los 7 días de la semana que contiene `date`, de lunes a domingo */
export function weekDays(date: Date): Date[] {
  return eachDayOfInterval({ start: weekStart(date), end: endOfWeek(date, WEEK_OPTIONS) })
}

/** 1 = lunes … 7 = domingo */
export function isoWeekday(date: Date): IsoWeekday {
  return getISODay(date) as IsoWeekday
}

/**
 * Semanas del mes para pintar un calendario: cada fila tiene 7 huecos (lunes a domingo)
 * y los días que no son del mes quedan a null.
 */
export function monthGrid(month: Date): (Date | null)[][] {
  const days = eachDayOfInterval({
    start: weekStart(startOfMonth(month)),
    end: endOfWeek(endOfMonth(month), WEEK_OPTIONS),
  })

  const weeks: (Date | null)[][] = []
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7).map((day) => (isSameMonth(day, month) ? day : null)))
  }
  return weeks
}

/** "09:00" → "9 AM", "19:30" → "7:30 PM", "12:00" → "12 PM", "00:15" → "12:15 AM" */
export function formatTime12h(time: string): string {
  const [hoursText = '0', minutes = '00'] = time.split(':')
  const hours = Number(hoursText)
  const suffix = hours < 12 ? 'AM' : 'PM'
  const hours12 = hours % 12 === 0 ? 12 : hours % 12
  return minutes === '00' ? `${hours12} ${suffix}` : `${hours12}:${minutes} ${suffix}`
}

/**
 * Título de la vista Semana a partir de cualquier día de la semana:
 * "12 – 18 de octubre", "28 de septiembre – 4 de octubre",
 * "29 de diciembre de 2025 – 4 de enero de 2026".
 */
export function formatWeekRange(date: Date): string {
  const start = weekStart(date)
  const end = addDays(start, 6)

  if (!isSameYear(start, end)) {
    return `${format(start, "d 'de' MMMM 'de' yyyy", { locale: es })} – ${format(end, "d 'de' MMMM 'de' yyyy", { locale: es })}`
  }
  if (!isSameMonth(start, end)) {
    return `${format(start, "d 'de' MMMM", { locale: es })} – ${format(end, "d 'de' MMMM", { locale: es })}`
  }
  return `${format(start, 'd')} – ${format(end, "d 'de' MMMM", { locale: es })}`
}

/** "Octubre 2026" */
export function formatMonthYear(date: Date): string {
  return capitalize(format(date, 'LLLL yyyy', { locale: es }))
}

/** "Octubre" */
export function formatMonthName(date: Date): string {
  return capitalize(format(date, 'LLLL', { locale: es }))
}

/** "Lunes" */
export function formatDayName(date: Date): string {
  return capitalize(format(date, 'EEEE', { locale: es }))
}

/** "12 oct" */
export function formatShortDate(date: Date): string {
  return format(date, 'd MMM', { locale: es }).replace('.', '')
}

/** "Jueves 15 de octubre" */
export function formatDayLong(date: Date): string {
  return capitalize(format(date, "EEEE d 'de' MMMM", { locale: es }))
}

/** "1 tarea", "4 tareas" */
export function formatTaskCount(count: number): string {
  return count === 1 ? '1 tarea' : `${count} tareas`
}

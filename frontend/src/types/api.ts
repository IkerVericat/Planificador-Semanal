// Tipos que reflejan el contrato de la API (ver tabla "Contrato de la API" en CLAUDE.md).
// Fechas locales en formato YYYY-MM-DD, horas en HH:mm, timestamps en ISO 8601.

export type ViewName = 'week' | 'month' | 'year'

export type Recurrence = 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly'

/** 1 = lunes … 7 = domingo */
export type IsoWeekday = 1 | 2 | 3 | 4 | 5 | 6 | 7

export interface User {
  id: string
  name: string
  email: string
  timezone: string
  /** 0 = domingo … 6 = sábado (1 = lunes) */
  week_starts_on: number
  default_view: ViewName
  created_at: string | null
}

export interface AuthResponse {
  user: User
  token: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  timezone?: string
  device_name?: string
}

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export interface ResetPasswordPayload {
  email: string
  token: string
  password: string
  password_confirmation: string
}

export type ProfilePayload = Partial<Pick<User, 'name' | 'timezone' | 'week_starts_on' | 'default_view'>>

export interface Category {
  id: string
  name: string
  /** #RRGGBB */
  color: string
  position: number
}

export type CategoryPayload = Partial<Omit<Category, 'id'>>

/** Tarea "plantilla" (GET/POST/PATCH /api/tasks) */
export interface Task {
  id: string
  category_id: string | null
  title: string
  notes: string | null
  task_date: string
  start_time: string | null
  end_time: string | null
  is_completed: boolean
  completed_at: string | null
  recurrence: Recurrence
  recurrence_days: IsoWeekday[] | null
  recurrence_until: string | null
  created_at: string | null
  updated_at: string | null
}

export interface TaskPayload {
  title?: string
  notes?: string | null
  task_date?: string
  start_time?: string | null
  end_time?: string | null
  category_id?: string | null
  is_completed?: boolean
  recurrence?: Recurrence
  recurrence_days?: IsoWeekday[] | null
  recurrence_until?: string | null
}

/** Una tarea en un día concreto, con recurrencias y excepciones ya aplicadas (GET /api/occurrences) */
export interface Occurrence {
  task_id: string
  /** Día en el que se muestra (puede ser distinto del original si se ha movido) */
  date: string
  /** Día que le corresponde según la repetición; es el {date} de PUT /tasks/{id}/occurrences/{date} */
  original_date: string
  start_time: string | null
  end_time: string | null
  title: string
  notes: string | null
  category_id: string | null
  is_completed: boolean
  is_recurring: boolean
}

export interface OccurrencePayload {
  is_completed?: boolean
  is_skipped?: boolean
  /** Mueve solo esta ocurrencia a otro día; null la devuelve a su día */
  moved_to_date?: string | null
}

export interface OccurrenceState {
  task_id: string
  date: string
  is_completed: boolean
  is_skipped: boolean
  moved_to_date: string | null
}

/** { "2026-10-15": 4, … } solo con los días que tienen tareas (GET /api/stats/days) */
export type DayStats = Record<string, number>

/** Error de validación de Laravel (422) */
export interface ValidationErrorBody {
  message: string
  errors: Record<string, string[]>
}

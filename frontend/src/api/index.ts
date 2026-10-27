import { http } from './client'
import type {
  AuthResponse,
  Category,
  CategoryPayload,
  DayStats,
  LoginPayload,
  Occurrence,
  OccurrencePayload,
  OccurrenceState,
  ProfilePayload,
  RegisterPayload,
  ResetPasswordPayload,
  Task,
  TaskPayload,
  User,
} from '@/types/api'

export { ApiError, getToken, setToken, setUnauthorizedHandler } from './client'

export const authApi = {
  register: (payload: RegisterPayload) => http.post<AuthResponse>('/auth/register', payload),
  login: (payload: LoginPayload) => http.post<AuthResponse>('/auth/login', payload),
  logout: () => http.post<void>('/auth/logout'),
  forgotPassword: (email: string) => http.post<{ message: string }>('/auth/forgot-password', { email }),
  resetPassword: (payload: ResetPasswordPayload) =>
    http.post<{ message: string }>('/auth/reset-password', payload),
}

export const profileApi = {
  get: () => http.get<User>('/me'),
  update: (payload: ProfilePayload) => http.patch<User>('/me', payload),
}

export const categoriesApi = {
  list: () => http.get<Category[]>('/categories'),
  create: (payload: CategoryPayload) => http.post<Category>('/categories', payload),
  update: (id: string, payload: CategoryPayload) => http.patch<Category>(`/categories/${id}`, payload),
  remove: (id: string) => http.delete(`/categories/${id}`),
}

export const tasksApi = {
  get: (id: string) => http.get<Task>(`/tasks/${id}`),
  create: (payload: TaskPayload) => http.post<Task>('/tasks', payload),
  update: (id: string, payload: TaskPayload) => http.patch<Task>(`/tasks/${id}`, payload),
  remove: (id: string) => http.delete(`/tasks/${id}`),
  updateOccurrence: (id: string, date: string, payload: OccurrencePayload) =>
    http.put<OccurrenceState>(`/tasks/${id}/occurrences/${date}`, payload),
}

export const occurrencesApi = {
  list: (from: string, to: string) => http.get<Occurrence[]>('/occurrences', { from, to }),
}

export const statsApi = {
  days: (year: number) => http.get<DayStats>('/stats/days', { year }),
}

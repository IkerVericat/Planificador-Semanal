import type { ValidationErrorBody } from '@/types/api'

export const TOKEN_KEY = 'planner_token'

const baseUrl = (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '')

export class ApiError extends Error {
  constructor(
    readonly status: number,
    message: string,
    /** Errores de validación por campo (solo en 422) */
    readonly errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }

  /** Primer mensaje de error de un campo, o undefined */
  field(name: string): string | undefined {
    return this.errors[name]?.[0]
  }
}

export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token: string | null): void {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    // Sin localStorage (modo privado estricto): la sesión dura lo que la pestaña
  }
}

let onUnauthorized: () => void = () => {}

/** Qué hacer cuando la API responde 401 con un token guardado (lo registra el router). */
export function setUnauthorizedHandler(handler: () => void): void {
  onUnauthorized = handler
}

type Query = Record<string, string | number | undefined>

interface RequestOptions {
  body?: unknown
  query?: Query
}

async function request<T>(method: string, path: string, options: RequestOptions = {}): Promise<T> {
  const url = new URL(`${baseUrl}/api${path}`, window.location.origin)
  for (const [key, value] of Object.entries(options.query ?? {})) {
    if (value !== undefined) url.searchParams.set(key, String(value))
  }

  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`
  if (options.body !== undefined) headers['Content-Type'] = 'application/json'

  let response: Response
  try {
    response = await fetch(url, {
      method,
      headers,
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
    })
  } catch {
    throw new ApiError(0, 'No se pudo conectar con el servidor. Revisa tu conexión.')
  }

  if (response.status === 204) return undefined as T

  const data: unknown = await response.json().catch(() => null)

  if (response.ok) return data as T

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized()
  }

  const body = (data ?? {}) as Partial<ValidationErrorBody>
  throw new ApiError(response.status, messageFor(response.status, body.message), body.errors ?? {})
}

function messageFor(status: number, message: string | undefined): string {
  if (status === 422 && message) return message
  if (status === 429) return 'Demasiados intentos. Espera un minuto y vuelve a probar.'
  if (status === 401) return 'Tu sesión ha caducado. Inicia sesión de nuevo.'
  if (status === 404) return 'No se ha encontrado.'
  return message && status < 500 ? message : 'Algo ha fallado. Inténtalo de nuevo.'
}

export const http = {
  get: <T>(path: string, query?: Query) => request<T>('GET', path, { query }),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, { body }),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, { body }),
  patch: <T>(path: string, body?: unknown) => request<T>('PATCH', path, { body }),
  delete: <T = void>(path: string) => request<T>('DELETE', path),
}

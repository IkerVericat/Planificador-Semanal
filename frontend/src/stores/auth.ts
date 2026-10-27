import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi, getToken, profileApi, setToken } from '@/api'
import type { AuthResponse, LoginPayload, ProfilePayload, RegisterPayload, User } from '@/types/api'
import { useCategoriesStore } from './categories'
import { usePlannerStore } from './planner'

/** Nombre del token en la API, para distinguir dispositivos */
function deviceName(): string {
  return /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) ? 'móvil' : 'web'
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getToken())
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => token.value !== null)

  function startSession(response: AuthResponse): void {
    setToken(response.token)
    token.value = response.token
    user.value = response.user
  }

  async function login(payload: LoginPayload): Promise<void> {
    startSession(await authApi.login({ ...payload, device_name: deviceName() }))
  }

  async function register(payload: RegisterPayload): Promise<void> {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    startSession(await authApi.register({ ...payload, timezone, device_name: deviceName() }))
  }

  /** Carga el usuario del token guardado (al abrir la app) */
  async function fetchUser(): Promise<User | null> {
    if (!token.value) return null
    user.value ??= await profileApi.get()
    return user.value
  }

  async function updateProfile(payload: ProfilePayload): Promise<void> {
    user.value = await profileApi.update(payload)
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      clearSession()
    }
  }

  /** Olvida el token y los datos del usuario (también tras un 401) */
  function clearSession(): void {
    setToken(null)
    token.value = null
    user.value = null
    useCategoriesStore().reset()
    usePlannerStore().reset()
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    register,
    fetchUser,
    updateProfile,
    logout,
    clearSession,
  }
})

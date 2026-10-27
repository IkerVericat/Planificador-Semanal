import { createRouter, createWebHistory } from 'vue-router'
import { ApiError, setUnauthorizedHandler } from '@/api'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Solo con sesión iniciada */
    auth?: boolean
    /** Solo sin sesión (login, registro…) */
    guest?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/ForgotPasswordView.vue'),
      meta: { guest: true },
    },
    {
      // Enlace del correo: /reset-password?token=…&email=…
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/views/ResetPasswordView.vue'),
    },
    {
      path: '/',
      component: () => import('@/views/PlannerView.vue'),
      meta: { auth: true },
      children: [
        // Redirige a la vista por defecto del usuario (ver beforeEach)
        { path: '', name: 'home', component: { render: () => null } },
        { path: 'week', name: 'week', component: () => import('@/views/planner/WeekView.vue') },
        { path: 'month', name: 'month', component: () => import('@/views/planner/MonthView.vue') },
        { path: 'year', name: 'year', component: () => import('@/views/planner/YearView.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.auth && !auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'home' }
  }

  if (to.meta.auth) {
    try {
      await auth.fetchUser()
    } catch (e) {
      // 401: el manejador de abajo ya ha cerrado la sesión
      if (e instanceof ApiError && e.status === 401) return { name: 'login' }
    }
  }

  if (to.name === 'home') {
    return { name: auth.user?.default_view ?? 'week', query: to.query }
  }
})

setUnauthorizedHandler(() => {
  useAuthStore().clearSession()
  if (router.currentRoute.value.meta.auth) router.replace({ name: 'login' })
})

export default router

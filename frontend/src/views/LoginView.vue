<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError } from '@/api'
import AuthLayout from '@/components/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
})
const error = ref<ApiError | null>(null)
const submitting = ref(false)

async function submit(): Promise<void> {
  submitting.value = true
  error.value = null
  try {
    await auth.login(form)
    const redirect = route.query.redirect
    await router.replace(typeof redirect === 'string' && redirect.startsWith('/') ? redirect : { name: 'home' })
  } catch (e) {
    error.value = e instanceof ApiError ? e : new ApiError(0, 'Algo ha fallado. Inténtalo de nuevo.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Bienvenido de nuevo" subtitle="Inicia sesión para ver tu semana">
    <form class="space-y-4" novalidate @submit.prevent="submit">
      <p v-if="error && !error.field('email') && !error.field('password')" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {{ error.message }}
      </p>

      <div>
        <label class="label" for="email">Correo electrónico</label>
        <input id="email" v-model="form.email" class="input" type="email" autocomplete="email" placeholder="tu@correo.com" required />
        <p v-if="error?.field('email')" class="field-error" role="alert">{{ error.field('email') }}</p>
      </div>

      <div>
        <label class="label" for="password">Contraseña</label>
        <input id="password" v-model="form.password" class="input" type="password" autocomplete="current-password" required />
        <p v-if="error?.field('password')" class="field-error">{{ error.field('password') }}</p>
      </div>

      <RouterLink :to="{ name: 'forgot-password' }" class="inline-flex min-h-11 items-center text-sm text-primary hover:underline">
        ¿Olvidaste tu contraseña?
      </RouterLink>

      <button type="submit" class="btn-primary" :disabled="submitting">
        {{ submitting ? 'Entrando…' : 'Iniciar sesión' }}
      </button>
    </form>

    <template #footer>
      ¿No tienes cuenta?
      <RouterLink :to="{ name: 'register' }" class="font-semibold text-primary hover:underline">Regístrate</RouterLink>
    </template>
  </AuthLayout>
</template>

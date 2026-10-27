<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '@/api'
import AuthLayout from '@/components/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref<ApiError | null>(null)
const submitting = ref(false)

/** Comprobaciones rápidas antes de llamar a la API (la API vuelve a validarlo todo) */
function localErrors(): Record<string, string[]> {
  const errors: Record<string, string[]> = {}
  if (form.password.length < 8) errors.password = ['La contraseña debe tener al menos 8 caracteres.']
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = ['Las contraseñas no coinciden.']
  }
  return errors
}

async function submit(): Promise<void> {
  const errors = localErrors()
  if (Object.keys(errors).length) {
    error.value = new ApiError(422, 'Revisa los datos.', errors)
    return
  }

  submitting.value = true
  error.value = null
  try {
    await auth.register(form)
    await router.replace({ name: 'home' })
  } catch (e) {
    error.value = e instanceof ApiError ? e : new ApiError(0, 'Algo ha fallado. Inténtalo de nuevo.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Crea tu cuenta" subtitle="Organiza tu semana en minutos">
    <form class="space-y-4" novalidate @submit.prevent="submit">
      <p v-if="error && Object.keys(error.errors).length === 0" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {{ error.message }}
      </p>

      <div>
        <label class="label" for="name">Nombre</label>
        <input id="name" v-model="form.name" class="input" type="text" autocomplete="name" placeholder="Tu nombre" required />
        <p v-if="error?.field('name')" class="field-error">{{ error.field('name') }}</p>
      </div>

      <div>
        <label class="label" for="email">Correo electrónico</label>
        <input id="email" v-model="form.email" class="input" type="email" autocomplete="email" placeholder="tu@correo.com" required />
        <p v-if="error?.field('email')" class="field-error">{{ error.field('email') }}</p>
      </div>

      <div>
        <label class="label" for="password">Contraseña</label>
        <input id="password" v-model="form.password" class="input" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" minlength="8" required />
        <p v-if="error?.field('password')" class="field-error">{{ error.field('password') }}</p>
      </div>

      <div>
        <label class="label" for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" placeholder="Repite la contraseña" required />
        <p v-if="error?.field('password_confirmation')" class="field-error">{{ error.field('password_confirmation') }}</p>
      </div>

      <button type="submit" class="btn-primary mt-6!" :disabled="submitting">
        {{ submitting ? 'Creando cuenta…' : 'Crear cuenta' }}
      </button>
    </form>

    <template #footer>
      ¿Ya tienes cuenta?
      <RouterLink :to="{ name: 'login' }" class="font-semibold text-primary hover:underline">Inicia sesión</RouterLink>
    </template>
  </AuthLayout>
</template>

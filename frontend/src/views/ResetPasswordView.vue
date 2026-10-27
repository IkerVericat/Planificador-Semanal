<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ApiError, authApi } from '@/api'
import AuthLayout from '@/components/AuthLayout.vue'

const route = useRoute()

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''))

const form = reactive({ password: '', password_confirmation: '' })
const error = ref<ApiError | null>(null)
const done = ref(false)
const submitting = ref(false)

async function submit(): Promise<void> {
  if (form.password !== form.password_confirmation) {
    error.value = new ApiError(422, 'Revisa los datos.', {
      password_confirmation: ['Las contraseñas no coinciden.'],
    })
    return
  }

  submitting.value = true
  error.value = null
  try {
    await authApi.resetPassword({ email: email.value, token: token.value, ...form })
    done.value = true
  } catch (e) {
    error.value = e instanceof ApiError ? e : new ApiError(0, 'Algo ha fallado. Inténtalo de nuevo.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Nueva contraseña" :subtitle="email ? `Para ${email}` : 'Elige una contraseña nueva'">
    <div v-if="!token || !email" class="space-y-6">
      <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        El enlace no es válido. Pide uno nuevo.
      </p>
      <RouterLink :to="{ name: 'forgot-password' }" class="btn-primary">Pedir otro enlace</RouterLink>
    </div>

    <div v-else-if="done" class="space-y-6" role="status">
      <p class="rounded-xl bg-primary-soft px-4 py-3 text-sm">Tu contraseña se ha cambiado. Ya puedes iniciar sesión.</p>
      <RouterLink :to="{ name: 'login', query: { email } }" class="btn-primary">Iniciar sesión</RouterLink>
    </div>

    <form v-else class="space-y-4" novalidate @submit.prevent="submit">
      <div v-if="error?.field('email')" class="space-y-2 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <p>{{ error.field('email') }}</p>
        <RouterLink :to="{ name: 'forgot-password' }" class="font-semibold underline">Pedir otro enlace</RouterLink>
      </div>
      <p v-else-if="error && Object.keys(error.errors).length === 0" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {{ error.message }}
      </p>

      <div>
        <label class="label" for="password">Contraseña nueva</label>
        <input id="password" v-model="form.password" class="input" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" minlength="8" required />
        <p v-if="error?.field('password')" class="field-error">{{ error.field('password') }}</p>
      </div>

      <div>
        <label class="label" for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" placeholder="Repite la contraseña" required />
        <p v-if="error?.field('password_confirmation')" class="field-error">{{ error.field('password_confirmation') }}</p>
      </div>

      <button type="submit" class="btn-primary mt-6!" :disabled="submitting">
        {{ submitting ? 'Guardando…' : 'Guardar contraseña' }}
      </button>
    </form>
  </AuthLayout>
</template>

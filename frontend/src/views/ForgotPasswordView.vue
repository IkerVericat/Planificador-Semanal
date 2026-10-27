<script setup lang="ts">
import { ref } from 'vue'
import { ApiError, authApi } from '@/api'
import AuthLayout from '@/components/AuthLayout.vue'

const email = ref('')
const error = ref<ApiError | null>(null)
const sentMessage = ref<string | null>(null)
const submitting = ref(false)

async function submit(): Promise<void> {
  submitting.value = true
  error.value = null
  try {
    sentMessage.value = (await authApi.forgotPassword(email.value)).message
  } catch (e) {
    error.value = e instanceof ApiError ? e : new ApiError(0, 'Algo ha fallado. Inténtalo de nuevo.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="¿Olvidaste tu contraseña?" subtitle="Te enviaremos un enlace para crear una nueva">
    <div v-if="sentMessage" class="space-y-6" role="status">
      <p class="rounded-xl bg-primary-soft px-4 py-3 text-sm text-ink">{{ sentMessage }}</p>
      <RouterLink :to="{ name: 'login', query: { email } }" class="btn-primary">Volver a iniciar sesión</RouterLink>
    </div>

    <form v-else class="space-y-4" novalidate @submit.prevent="submit">
      <p v-if="error && !error.field('email')" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {{ error.message }}
      </p>

      <div>
        <label class="label" for="email">Correo electrónico</label>
        <input id="email" v-model="email" class="input" type="email" autocomplete="email" placeholder="tu@correo.com" required />
        <p v-if="error?.field('email')" class="field-error">{{ error.field('email') }}</p>
      </div>

      <button type="submit" class="btn-primary mt-6!" :disabled="submitting">
        {{ submitting ? 'Enviando…' : 'Continuar' }}
      </button>
    </form>

    <template #footer>
      ¿Te has acordado?
      <RouterLink :to="{ name: 'login' }" class="font-semibold text-primary hover:underline">Inicia sesión</RouterLink>
    </template>
  </AuthLayout>
</template>

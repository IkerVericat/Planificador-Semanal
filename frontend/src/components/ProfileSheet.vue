<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '@/api'
import { useAuthStore } from '@/stores/auth'
import type { ViewName } from '@/types/api'
import BottomSheet from './BottomSheet.vue'
import SegmentedControl from './SegmentedControl.vue'

defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: [] }>()

const auth = useAuthStore()
const router = useRouter()
const error = ref<string | null>(null)
const loggingOut = ref(false)

const VIEW_OPTIONS: { value: ViewName; label: string }[] = [
  { value: 'week', label: 'Semana' },
  { value: 'month', label: 'Mes' },
  { value: 'year', label: 'Año' },
]

const defaultView = computed<ViewName>({
  get: () => auth.user?.default_view ?? 'week',
  set: async (value) => {
    error.value = null
    try {
      await auth.updateProfile({ default_view: value })
    } catch (e) {
      error.value = e instanceof ApiError ? e.message : 'No se ha podido guardar.'
    }
  },
})

async function logout(): Promise<void> {
  loggingOut.value = true
  try {
    await auth.logout()
  } catch {
    // La sesión local ya se ha cerrado aunque falle la llamada
  } finally {
    loggingOut.value = false
    emit('close')
    await router.replace({ name: 'login' })
  }
}
</script>

<template>
  <BottomSheet :open="open" title="Tu cuenta" @close="emit('close')">
    <div v-if="auth.user" class="space-y-6">
      <div>
        <p class="font-semibold">{{ auth.user.name }}</p>
        <p class="text-sm text-muted">{{ auth.user.email }}</p>
      </div>

      <div>
        <p id="default-view-label" class="label">Vista al abrir la app</p>
        <SegmentedControl v-model="defaultView" :options="VIEW_OPTIONS" label="Vista al abrir la app" />
        <p v-if="error" class="field-error" role="alert">{{ error }}</p>
      </div>

      <button
        type="button"
        class="min-h-12 w-full rounded-xl border border-line font-medium text-red-600 hover:bg-red-50"
        :disabled="loggingOut"
        @click="logout"
      >
        Cerrar sesión
      </button>
    </div>
  </BottomSheet>
</template>

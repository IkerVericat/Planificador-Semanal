<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import FabButton from '@/components/FabButton.vue'
import ProfileSheet from '@/components/ProfileSheet.vue'
import SegmentedControl from '@/components/SegmentedControl.vue'
import TaskForm from '@/components/TaskForm.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import type { ViewName } from '@/types/api'
import { toISODate } from '@/utils/dates'

const route = useRoute()
const router = useRouter()
const planner = usePlannerStore()
const categories = useCategoriesStore()
const { anchor } = useAnchorDate()

const profileOpen = ref(false)

const VIEW_OPTIONS: { value: ViewName; label: string }[] = [
  { value: 'week', label: 'Semana' },
  { value: 'month', label: 'Mes' },
  { value: 'year', label: 'Año' },
]

const view = computed<ViewName>({
  get: () => (route.name === 'month' || route.name === 'year' ? route.name : 'week'),
  set: (name) => router.push({ name, query: route.query }),
})

const editorTitle = computed(() => (planner.editor?.mode === 'edit' ? 'Editar tarea' : 'Nueva tarea'))

onMounted(() => {
  categories.load().catch(() => {
    // Sin categorías las tareas se pintan en gris; se reintenta al volver a entrar
  })
})
</script>

<template>
  <div class="mx-auto min-h-dvh max-w-md px-4 pb-28 pt-[max(1.5rem,env(safe-area-inset-top))]">
    <header class="flex items-center justify-between">
      <h1 class="text-[26px] font-bold">Mi planificador</h1>
      <button
        type="button"
        class="flex size-11 items-center justify-center rounded-full bg-primary-soft text-primary transition hover:bg-primary/15"
        aria-label="Tu cuenta"
        @click="profileOpen = true"
      >
        <AppIcon name="user" />
      </button>
    </header>

    <SegmentedControl v-model="view" :options="VIEW_OPTIONS" label="Vista" class="mt-5" />

    <main class="mt-4">
      <RouterView />
    </main>

    <FabButton label="Nueva tarea" @click="planner.openCreate(toISODate(anchor))" />

    <BottomSheet :open="planner.editor !== null" :title="editorTitle" @close="planner.closeEditor()">
      <TaskForm v-if="planner.editor" :editor="planner.editor" @done="planner.closeEditor()" />
    </BottomSheet>

    <ProfileSheet :open="profileOpen" @close="profileOpen = false" />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import FabButton from '@/components/FabButton.vue'
import ProfileSheet from '@/components/ProfileSheet.vue'
import SegmentedControl from '@/components/SegmentedControl.vue'
import TaskForm from '@/components/TaskForm.vue'
import UndoToast from '@/components/UndoToast.vue'
import { useAnchorDate } from '@/composables/useAnchorDate'
import { useSwipe } from '@/composables/useSwipe'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore } from '@/stores/planner'
import type { ViewName } from '@/types/api'
import { toISODate } from '@/utils/dates'

const route = useRoute()
const router = useRouter()
const planner = usePlannerStore()
const categories = useCategoriesStore()
const { anchor, shift, goToday } = useAnchorDate()

const profileOpen = ref(false)
const content = ref<HTMLElement | null>(null)

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

// En el móvil, deslizar a la izquierda avanza y a la derecha retrocede
useSwipe(content, (direction) => shift(direction === 'left' ? 1 : -1))

function isTyping(target: EventTarget | null): boolean {
  return target instanceof HTMLElement && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))
}

/** Atajos de teclado (ordenador). No actúan mientras se escribe ni con una hoja abierta. */
function onKeyDown(event: KeyboardEvent): void {
  if (isTyping(event.target) || planner.editor || profileOpen.value) return

  if ((event.ctrlKey || event.metaKey) && !event.shiftKey && event.key.toLowerCase() === 'z') {
    if (planner.toast?.undo) {
      event.preventDefault()
      planner.undoToast()
    }
    return
  }
  if (event.ctrlKey || event.metaKey || event.altKey) return

  const views: Record<string, ViewName> = { '1': 'week', '2': 'month', '3': 'year' }
  const actions: Record<string, () => void> = {
    n: () => planner.openCreate(toISODate(anchor.value)),
    t: goToday,
    ArrowLeft: () => shift(-1),
    ArrowRight: () => shift(1),
  }
  const action = actions[event.key.length === 1 ? event.key.toLowerCase() : event.key]
  const nextView = views[event.key]

  if (action) action()
  else if (nextView) view.value = nextView
  else return
  event.preventDefault()
}

onMounted(() => {
  window.addEventListener('keydown', onKeyDown)
  categories.load().catch(() => {
    // Sin categorías las tareas se pintan en gris; se reintenta al volver a entrar
  })
})

onBeforeUnmount(() => window.removeEventListener('keydown', onKeyDown))
</script>

<template>
  <div
    class="mx-auto min-h-dvh max-w-md px-4 pb-28 pt-[max(1.5rem,env(safe-area-inset-top))] md:max-w-3xl md:px-6 lg:max-w-6xl lg:px-8 lg:pb-12 lg:pt-8"
  >
    <!-- Móvil: título y perfil arriba, selector debajo. Ordenador: todo en una fila con el botón "Nueva tarea" -->
    <header class="grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-5 lg:grid-cols-[1fr_auto_auto_auto]">
      <h1 class="order-1 text-[26px] font-bold lg:text-3xl">Mi planificador</h1>
      <SegmentedControl
        v-model="view"
        :options="VIEW_OPTIONS"
        label="Vista"
        class="order-3 col-span-2 lg:order-2 lg:col-span-1 lg:w-80"
      />
      <button
        type="button"
        class="order-4 hidden h-11 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-medium text-white transition hover:bg-primary-dark lg:order-3 lg:flex"
        title="Nueva tarea (N)"
        aria-keyshortcuts="N"
        @click="planner.openCreate(toISODate(anchor))"
      >
        <AppIcon name="plus" :size="18" />
        Nueva tarea
      </button>
      <button
        type="button"
        class="order-2 flex size-11 items-center justify-center rounded-full bg-primary-soft text-primary transition hover:bg-primary/15 lg:order-4"
        aria-label="Tu cuenta"
        @click="profileOpen = true"
      >
        <AppIcon name="user" />
      </button>
    </header>

    <main ref="content" class="mt-4 lg:mt-6">
      <RouterView />
    </main>

    <FabButton label="Nueva tarea" class="lg:hidden" @click="planner.openCreate(toISODate(anchor))" />

    <BottomSheet :open="planner.editor !== null" :title="editorTitle" @close="planner.closeEditor()">
      <TaskForm v-if="planner.editor" :editor="planner.editor" @done="planner.closeEditor()" />
    </BottomSheet>

    <ProfileSheet :open="profileOpen" @close="profileOpen = false" />

    <UndoToast />
  </div>
</template>

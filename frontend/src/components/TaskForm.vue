<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { ApiError, tasksApi } from '@/api'
import { useCategoriesStore } from '@/stores/categories'
import { usePlannerStore, type TaskEditor } from '@/stores/planner'
import type { IsoWeekday, Recurrence, Task, TaskPayload } from '@/types/api'
import { WEEKDAY_INITIALS, fromISODate, isoWeekday } from '@/utils/dates'

const props = defineProps<{ editor: TaskEditor }>()
const emit = defineEmits<{ done: [] }>()

const planner = usePlannerStore()
const categories = useCategoriesStore()

const RECURRENCE_OPTIONS: { value: Recurrence; label: string }[] = [
  { value: 'none', label: 'No se repite' },
  { value: 'daily', label: 'Cada día' },
  { value: 'weekly', label: 'Cada semana' },
  { value: 'monthly', label: 'Cada mes' },
  { value: 'yearly', label: 'Cada año' },
]

const WEEKDAY_NAMES = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']

const form = reactive({
  title: '',
  task_date: '',
  all_day: false,
  start_time: '09:00',
  end_time: '',
  category_id: null as string | null,
  recurrence: 'none' as Recurrence,
  recurrence_days: [] as IsoWeekday[],
  recurrence_until: '',
  notes: '',
})

const task = ref<Task | null>(null)
const loadingTask = ref(false)
const saving = ref(false)
const error = ref<ApiError | null>(null)

const isEdit = computed(() => props.editor.mode === 'edit')
const occurrence = computed(() => (props.editor.mode === 'edit' ? props.editor.occurrence : null))

function fieldError(name: string): string | undefined {
  return error.value?.field(name)
}

function resetForm(date: string): void {
  Object.assign(form, {
    title: '',
    task_date: date,
    all_day: false,
    start_time: '09:00',
    end_time: '',
    category_id: null,
    recurrence: 'none',
    recurrence_days: [isoWeekday(fromISODate(date))],
    recurrence_until: '',
    notes: '',
  })
}

function fillFromTask(value: Task): void {
  Object.assign(form, {
    title: value.title,
    task_date: value.task_date,
    all_day: value.start_time === null,
    start_time: value.start_time ?? '09:00',
    end_time: value.end_time ?? '',
    category_id: value.category_id,
    recurrence: value.recurrence,
    recurrence_days: value.recurrence_days ?? [isoWeekday(fromISODate(value.task_date))],
    recurrence_until: value.recurrence_until ?? '',
    notes: value.notes ?? '',
  })
}

watch(
  () => props.editor,
  async (editor) => {
    error.value = null
    task.value = null
    if (editor.mode === 'create') {
      resetForm(editor.date)
      return
    }
    loadingTask.value = true
    try {
      task.value = await tasksApi.get(editor.occurrence.task_id)
      fillFromTask(task.value)
    } catch (e) {
      error.value = e instanceof ApiError ? e : new ApiError(0, 'No se ha podido cargar la tarea.')
    } finally {
      loadingTask.value = false
    }
  },
  { immediate: true },
)

function toggleWeekday(day: IsoWeekday): void {
  form.recurrence_days = form.recurrence_days.includes(day)
    ? form.recurrence_days.filter((d) => d !== day)
    : [...form.recurrence_days, day].sort()
}

function payload(): TaskPayload {
  const repeats = form.recurrence !== 'none'
  return {
    title: form.title.trim(),
    task_date: form.task_date,
    start_time: form.all_day ? null : form.start_time || null,
    end_time: form.all_day ? null : form.end_time || null,
    category_id: form.category_id,
    recurrence: form.recurrence,
    recurrence_days: form.recurrence === 'weekly' && form.recurrence_days.length ? form.recurrence_days : null,
    recurrence_until: repeats && form.recurrence_until ? form.recurrence_until : null,
    notes: form.notes.trim() || null,
  }
}

async function run(action: () => Promise<void>): Promise<void> {
  saving.value = true
  error.value = null
  try {
    await action()
    emit('done')
  } catch (e) {
    error.value = e instanceof ApiError ? e : new ApiError(0, 'Algo ha fallado. Inténtalo de nuevo.')
  } finally {
    saving.value = false
  }
}

function submit(): Promise<void> {
  return run(() =>
    task.value ? planner.updateTask(task.value.id, payload()) : planner.createTask(payload()),
  )
}

function toggleCompleted(): Promise<void> {
  return run(() => planner.toggleCompleted(occurrence.value!))
}

function skipThisDay(): Promise<void> {
  return run(() => planner.updateOccurrence(occurrence.value!, { is_skipped: true }))
}

function remove(): Promise<void> {
  const message = task.value?.recurrence !== 'none'
    ? '¿Eliminar esta tarea y todas sus repeticiones?'
    : '¿Eliminar esta tarea?'
  if (!task.value || !window.confirm(message)) return Promise.resolve()
  const id = task.value.id
  return run(() => planner.deleteTask(id))
}
</script>

<template>
  <p v-if="loadingTask" class="py-10 text-center text-muted">Cargando…</p>

  <form v-else class="space-y-4" novalidate @submit.prevent="submit">
    <p
      v-if="error && Object.keys(error.errors).length === 0"
      class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      {{ error.message }}
    </p>

    <div v-if="occurrence" class="flex flex-wrap gap-2">
      <button
        type="button"
        class="min-h-11 flex-1 rounded-xl border border-line px-3 text-sm font-medium"
        :class="occurrence.is_completed ? 'bg-white' : 'bg-primary-soft text-primary'"
        :disabled="saving"
        @click="toggleCompleted"
      >
        {{ occurrence.is_completed ? 'Marcar como pendiente' : 'Marcar como hecha' }}
      </button>
      <button
        v-if="occurrence.is_recurring"
        type="button"
        class="min-h-11 flex-1 rounded-xl border border-line px-3 text-sm font-medium"
        :disabled="saving"
        @click="skipThisDay"
      >
        Quitar solo este día
      </button>
    </div>

    <div>
      <label class="label" for="task-title">Título</label>
      <input id="task-title" v-model="form.title" class="input" type="text" maxlength="255" required />
      <p v-if="fieldError('title')" class="field-error">{{ fieldError('title') }}</p>
    </div>

    <div>
      <label class="label" for="task-date">{{ form.recurrence === 'none' ? 'Fecha' : 'Empieza el' }}</label>
      <input id="task-date" v-model="form.task_date" class="input" type="date" required />
      <p v-if="fieldError('task_date')" class="field-error">{{ fieldError('task_date') }}</p>
    </div>

    <label class="flex min-h-11 items-center justify-between gap-4">
      <span>Todo el día</span>
      <input v-model="form.all_day" type="checkbox" class="size-5 accent-primary" />
    </label>

    <div v-if="!form.all_day" class="grid grid-cols-2 gap-3">
      <div>
        <label class="label" for="task-start">Hora de inicio</label>
        <input id="task-start" v-model="form.start_time" class="input" type="time" />
        <p v-if="fieldError('start_time')" class="field-error">{{ fieldError('start_time') }}</p>
      </div>
      <div>
        <label class="label" for="task-end">Hora de fin <span class="text-muted/70">(opcional)</span></label>
        <input id="task-end" v-model="form.end_time" class="input" type="time" />
        <p v-if="fieldError('end_time')" class="field-error">{{ fieldError('end_time') }}</p>
      </div>
    </div>

    <fieldset>
      <legend class="label">Categoría</legend>
      <div class="flex flex-wrap gap-2">
        <label
          v-for="category in categories.items"
          :key="category.id"
          class="flex min-h-11 cursor-pointer items-center rounded-lg px-3 text-xs font-semibold uppercase tracking-wide ring-offset-2 has-[:checked]:ring-2 has-[:checked]:ring-primary has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-primary"
          :style="{ backgroundColor: category.color }"
        >
          <input v-model="form.category_id" type="radio" name="category" :value="category.id" class="sr-only" />
          {{ category.name }}
        </label>
        <label
          class="flex min-h-11 cursor-pointer items-center rounded-lg border border-line px-3 text-xs font-semibold uppercase tracking-wide text-muted ring-offset-2 has-[:checked]:ring-2 has-[:checked]:ring-primary has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-primary"
        >
          <input v-model="form.category_id" type="radio" name="category" :value="null" class="sr-only" />
          Sin categoría
        </label>
      </div>
      <p v-if="fieldError('category_id')" class="field-error">{{ fieldError('category_id') }}</p>
    </fieldset>

    <div>
      <label class="label" for="task-recurrence">Repetir</label>
      <select id="task-recurrence" v-model="form.recurrence" class="input appearance-none">
        <option v-for="option in RECURRENCE_OPTIONS" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>

    <fieldset v-if="form.recurrence === 'weekly'">
      <legend class="label">Días</legend>
      <div class="flex justify-between gap-1">
        <button
          v-for="(initial, index) in WEEKDAY_INITIALS"
          :key="initial"
          type="button"
          class="flex size-11 items-center justify-center rounded-full text-sm font-semibold transition"
          :class="form.recurrence_days.includes((index + 1) as IsoWeekday) ? 'bg-primary text-white' : 'bg-surface text-muted'"
          :aria-pressed="form.recurrence_days.includes((index + 1) as IsoWeekday)"
          :aria-label="WEEKDAY_NAMES[index]"
          @click="toggleWeekday((index + 1) as IsoWeekday)"
        >
          {{ initial }}
        </button>
      </div>
      <p v-if="fieldError('recurrence_days')" class="field-error">{{ fieldError('recurrence_days') }}</p>
    </fieldset>

    <div v-if="form.recurrence !== 'none'">
      <label class="label" for="task-until">Hasta <span class="text-muted/70">(opcional)</span></label>
      <input id="task-until" v-model="form.recurrence_until" class="input" type="date" :min="form.task_date" />
      <p v-if="fieldError('recurrence_until')" class="field-error">{{ fieldError('recurrence_until') }}</p>
    </div>

    <div>
      <label class="label" for="task-notes">Notas</label>
      <textarea id="task-notes" v-model="form.notes" class="input min-h-24 py-3" rows="3" maxlength="5000" />
      <p v-if="fieldError('notes')" class="field-error">{{ fieldError('notes') }}</p>
    </div>

    <p v-if="task && task.recurrence !== 'none'" class="text-sm text-muted">
      Los cambios se aplican a todas las repeticiones.
    </p>

    <button type="submit" class="btn-primary" :disabled="saving">
      {{ saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Crear tarea' }}
    </button>

    <button
      v-if="task"
      type="button"
      class="min-h-11 w-full rounded-xl text-sm font-medium text-red-600 hover:bg-red-50"
      :disabled="saving"
      @click="remove"
    >
      {{ task.recurrence !== 'none' ? 'Eliminar todas las repeticiones' : 'Eliminar tarea' }}
    </button>
  </form>
</template>

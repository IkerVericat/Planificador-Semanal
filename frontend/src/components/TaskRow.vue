<script setup lang="ts">
import { computed } from 'vue'
import type { Occurrence } from '@/types/api'
import { formatTime12h } from '@/utils/dates'
import AppIcon from './AppIcon.vue'

const props = defineProps<{ occurrence: Occurrence; color: string }>()
const emit = defineEmits<{ select: [occurrence: Occurrence] }>()

const time = computed(() =>
  props.occurrence.start_time ? formatTime12h(props.occurrence.start_time) : 'Todo el día',
)

const accessibleLabel = computed(() => {
  const parts = [time.value, props.occurrence.title]
  if (props.occurrence.is_completed) parts.push('hecha')
  if (props.occurrence.is_recurring) parts.push('se repite')
  return parts.join(', ')
})
</script>

<template>
  <button
    type="button"
    class="flex w-full gap-2 rounded-lg text-left text-ink transition active:scale-[0.99]"
    :class="{ 'opacity-50': occurrence.is_completed }"
    :aria-label="accessibleLabel"
    @click="emit('select', occurrence)"
  >
    <span
      class="flex min-h-11 w-[68px] shrink-0 items-center justify-center rounded-lg px-1 text-center font-semibold leading-tight"
      :class="occurrence.start_time ? 'whitespace-nowrap text-[12px]' : 'text-[11px]'"
      :style="{ backgroundColor: color }"
    >
      {{ time }}
    </span>
    <span
      class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-lg px-3 py-2 text-[13px] font-bold uppercase tracking-wide"
      :style="{ backgroundColor: color }"
    >
      <span class="min-w-0 flex-1 break-words" :class="{ 'line-through': occurrence.is_completed }">
        {{ occurrence.title }}
      </span>
      <AppIcon v-if="occurrence.is_completed" name="check" :size="16" class="shrink-0" />
      <AppIcon v-else-if="occurrence.is_recurring" name="repeat" :size="14" class="shrink-0 opacity-50" />
    </span>
  </button>
</template>

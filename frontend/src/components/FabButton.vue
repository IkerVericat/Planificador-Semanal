<script setup lang="ts">
import AppIcon from './AppIcon.vue'

defineProps<{ label: string }>()
const emit = defineEmits<{ click: [] }>()
</script>

<template>
  <button
    type="button"
    class="fixed bottom-[max(1.5rem,env(safe-area-inset-bottom))] right-[max(1.25rem,calc(50vw-14rem+1.25rem))] z-30 flex size-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-dark active:scale-95"
    :aria-label="label"
    @click="emit('click')"
  >
    <AppIcon name="plus" :size="26" />
  </button>
</template>

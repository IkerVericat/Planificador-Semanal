<script setup lang="ts" generic="T extends string">
defineProps<{
  options: { value: T; label: string }[]
  label: string
}>()

const model = defineModel<T>({ required: true })
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="flex rounded-xl bg-[#EBEDF5] p-1">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="model === option.value"
      class="min-h-10 flex-1 rounded-lg text-sm transition"
      :class="model === option.value ? 'bg-white font-semibold text-ink shadow-sm' : 'text-muted hover:text-ink'"
      @click="model = option.value"
    >
      {{ option.label }}
    </button>
  </div>
</template>

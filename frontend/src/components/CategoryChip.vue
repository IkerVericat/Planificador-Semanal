<script setup lang="ts">
defineProps<{ name: string; color: string }>()
</script>

<template>
  <span
    class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink"
    :style="{ backgroundColor: color }"
  >
    {{ name }}
  </span>
</template>

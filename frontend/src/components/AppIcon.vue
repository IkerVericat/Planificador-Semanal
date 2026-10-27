<script setup lang="ts">
// Iconos de trazo (estilo del mockup). Decorativos: el botón que los contiene lleva el aria-label.
export type IconName = 'calendar' | 'chevron-left' | 'chevron-right' | 'plus' | 'user' | 'close' | 'repeat' | 'check'

defineProps<{ name: IconName; size?: number }>()

const paths: Record<IconName, string> = {
  calendar: 'M8 3v3M16 3v3M4.5 9.5h15M6.5 5h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z',
  'chevron-left': 'M14.5 6 8.5 12l6 6',
  'chevron-right': 'M9.5 6l6 6-6 6',
  plus: 'M12 5v14M5 12h14',
  user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM5 20c.8-3.4 3.6-5.5 7-5.5s6.2 2.1 7 5.5',
  close: 'M6 6l12 12M18 6 6 18',
  repeat: 'M17 2.5 20.5 6 17 9.5M3.5 11V9.5a3.5 3.5 0 0 1 3.5-3.5h13.5M7 21.5 3.5 18 7 14.5M20.5 13v1.5a3.5 3.5 0 0 1-3.5 3.5H3.5',
  check: 'M5 12.5l4.5 4.5L19 7.5',
}
</script>

<template>
  <svg
    :width="size ?? 20"
    :height="size ?? 20"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name]" />
  </svg>
</template>

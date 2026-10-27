<script setup lang="ts">
import AppLogo from './AppLogo.vue'

defineProps<{ title: string; subtitle: string }>()
</script>

<template>
  <main class="mx-auto flex min-h-dvh max-w-md flex-col px-6 pb-8 pt-[max(4rem,env(safe-area-inset-top))]">
    <AppLogo />
    <h1 class="mt-6 text-[28px] font-bold leading-tight">{{ title }}</h1>
    <p class="mt-1 text-muted">{{ subtitle }}</p>
    <div class="mt-8">
      <slot />
    </div>
    <p v-if="$slots.footer" class="mt-auto pt-10 text-center text-sm text-muted">
      <slot name="footer" />
    </p>
  </main>
</template>

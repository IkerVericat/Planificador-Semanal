<script setup lang="ts">
import AppLogo from './AppLogo.vue'

defineProps<{ title: string; subtitle: string }>()
</script>

<template>
  <!-- Móvil: pantalla completa como en el mockup. Tablet y ordenador: tarjeta blanca centrada -->
  <main class="flex min-h-dvh md:items-center md:justify-center md:p-8">
    <div
      class="mx-auto flex min-h-dvh w-full max-w-md flex-col px-6 pb-8 pt-[max(4rem,env(safe-area-inset-top))] md:mx-0 md:min-h-0 md:rounded-3xl md:border md:border-line md:bg-card md:p-10 md:shadow-sm"
    >
      <AppLogo />
      <h1 class="mt-6 text-[28px] font-bold leading-tight">{{ title }}</h1>
      <p class="mt-1 text-muted">{{ subtitle }}</p>
      <div class="mt-8">
        <slot />
      </div>
      <p v-if="$slots.footer" class="mt-auto pt-10 text-center text-sm text-muted md:mt-0 md:pt-8">
        <slot name="footer" />
      </p>
    </div>
  </main>
</template>

import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { categoriesApi } from '@/api'
import type { Category } from '@/types/api'

/** Color de las tareas sin categoría */
export const NO_CATEGORY_COLOR = '#E6DFD2'

export const useCategoriesStore = defineStore('categories', () => {
  const items = ref<Category[]>([])
  const loaded = ref(false)

  const byId = computed(() => new Map(items.value.map((category) => [category.id, category])))

  async function load(force = false): Promise<void> {
    if (loaded.value && !force) return
    items.value = await categoriesApi.list()
    loaded.value = true
  }

  function colorFor(categoryId: string | null): string {
    return (categoryId && byId.value.get(categoryId)?.color) || NO_CATEGORY_COLOR
  }

  function reset(): void {
    items.value = []
    loaded.value = false
  }

  return { items, loaded, byId, load, colorFor, reset }
})

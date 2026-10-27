import { onBeforeUnmount, onMounted, type Ref } from 'vue'
import { dragState } from './useTaskDrag'

const MIN_DISTANCE = 60
const MAX_DURATION_MS = 600

/**
 * Deslizar el dedo a izquierda o derecha sobre `target`.
 * No interfiere con el scroll vertical ni con arrastrar tareas.
 */
export function useSwipe(target: Ref<HTMLElement | null>, onSwipe: (direction: 'left' | 'right') => void) {
  let startX = 0
  let startY = 0
  let startedAt = 0
  let cancelled = false

  function onTouchStart(e: TouchEvent): void {
    const touch = e.touches[0]
    cancelled = e.touches.length > 1 || !touch || (e.target as HTMLElement).closest('input, textarea, select') !== null
    if (!touch) return
    startX = touch.clientX
    startY = touch.clientY
    startedAt = Date.now()
  }

  function onTouchMove(): void {
    if (dragState.occurrence) cancelled = true
  }

  function onTouchEnd(e: TouchEvent): void {
    const touch = e.changedTouches[0]
    if (cancelled || !touch || Date.now() - startedAt > MAX_DURATION_MS) return
    const dx = touch.clientX - startX
    const dy = touch.clientY - startY
    if (Math.abs(dx) >= MIN_DISTANCE && Math.abs(dx) > Math.abs(dy) * 1.5) {
      onSwipe(dx < 0 ? 'left' : 'right')
    }
  }

  onMounted(() => {
    target.value?.addEventListener('touchstart', onTouchStart, { passive: true })
    target.value?.addEventListener('touchmove', onTouchMove, { passive: true })
    target.value?.addEventListener('touchend', onTouchEnd, { passive: true })
  })

  onBeforeUnmount(() => {
    target.value?.removeEventListener('touchstart', onTouchStart)
    target.value?.removeEventListener('touchmove', onTouchMove)
    target.value?.removeEventListener('touchend', onTouchEnd)
  })
}

import { reactive } from 'vue'
import type { Occurrence } from '@/types/api'

/** En táctil, mantener pulsado este tiempo para empezar a arrastrar (antes de eso es un scroll) */
const LONG_PRESS_MS = 350
/** Con ratón, píxeles que hay que mover para que deje de ser un clic */
const MOUSE_THRESHOLD = 6
/** En táctil, si el dedo se mueve más que esto antes de la pulsación larga, es un scroll */
const TOUCH_TOLERANCE = 8
/** Distancia al borde de la ventana a partir de la cual se hace scroll automático */
const EDGE = 72

/** Estado del arrastre en curso, compartido por las vistas (resaltar el día de destino) */
export const dragState = reactive({
  occurrence: null as Occurrence | null,
  overDate: null as string | null,
})

/** Evita el clic que el navegador lanza al soltar (abriría el formulario de la tarea) */
function suppressNextClick(): void {
  const block = (event: Event) => {
    event.stopPropagation()
    event.preventDefault()
  }
  window.addEventListener('click', block, { capture: true, once: true })
  setTimeout(() => window.removeEventListener('click', block, { capture: true }), 0)
}

/**
 * Arrastrar y soltar una tarea sobre otro día. Funciona con ratón y con el dedo
 * (pulsación larga) usando Pointer Events, sin dependencias.
 * Los destinos son elementos con `data-drop-date="YYYY-MM-DD"`.
 */
export function startTaskDrag(
  event: PointerEvent,
  occurrence: Occurrence,
  source: HTMLElement,
  onDrop: (occurrence: Occurrence, date: string) => void,
): void {
  if (event.button !== 0 || dragState.occurrence) return

  const isTouch = event.pointerType !== 'mouse'
  const startX = event.clientX
  const startY = event.clientY
  let lastX = startX
  let lastY = startY
  let active = false
  let ghost: HTMLElement | null = null
  let offsetX = 0
  let offsetY = 0
  let scrollFrame = 0
  const longPress = isTouch ? window.setTimeout(activate, LONG_PRESS_MS) : 0

  function activate(): void {
    active = true
    dragState.occurrence = occurrence

    const rect = source.getBoundingClientRect()
    offsetX = startX - rect.left
    offsetY = startY - rect.top

    ghost = source.cloneNode(true) as HTMLElement
    ghost.setAttribute('aria-hidden', 'true')
    Object.assign(ghost.style, {
      position: 'fixed',
      left: '0',
      top: '0',
      width: `${rect.width}px`,
      margin: '0',
      pointerEvents: 'none',
      zIndex: '60',
      opacity: '0.95',
      borderRadius: '0.5rem',
      boxShadow: '0 14px 32px rgb(28 29 43 / 0.25)',
    })
    document.body.appendChild(ghost)
    document.body.style.userSelect = 'none'
    if (isTouch) navigator.vibrate?.(12)

    update()
  }

  /** Mueve la copia bajo el puntero y busca el día que hay debajo */
  function update(): void {
    if (!ghost) return
    ghost.style.transform = `translate(${lastX - offsetX}px, ${lastY - offsetY}px) rotate(-1.5deg) scale(1.03)`
    const target = document.elementFromPoint(lastX, lastY)?.closest<HTMLElement>('[data-drop-date]')
    dragState.overDate = target?.dataset.dropDate ?? null
  }

  /** Scroll automático cerca de los bordes (lista de días en el móvil) */
  function autoScroll(): void {
    const speed = lastY < EDGE ? -(EDGE - lastY) / 4 : lastY > window.innerHeight - EDGE ? (lastY - window.innerHeight + EDGE) / 4 : 0
    if (speed !== 0) {
      window.scrollBy(0, speed)
      update()
    }
    scrollFrame = active ? requestAnimationFrame(autoScroll) : 0
  }

  function onPointerMove(e: PointerEvent): void {
    lastX = e.clientX
    lastY = e.clientY
    const distance = Math.hypot(lastX - startX, lastY - startY)

    if (!active) {
      if (isTouch) {
        if (distance > TOUCH_TOLERANCE) cleanup()
        return
      }
      if (distance < MOUSE_THRESHOLD) return
      activate()
    }

    update()
    if (!scrollFrame) scrollFrame = requestAnimationFrame(autoScroll)
  }

  function onPointerUp(): void {
    if (active) {
      suppressNextClick()
      const date = dragState.overDate
      if (date && date !== occurrence.date) onDrop(occurrence, date)
    }
    cleanup()
  }

  // Mientras se arrastra con el dedo, el gesto no debe hacer scroll de la página
  function onTouchMove(e: TouchEvent): void {
    if (active && e.cancelable) e.preventDefault()
  }

  // Evita el menú contextual de la pulsación larga en móvil
  function onContextMenu(e: Event): void {
    if (isTouch) e.preventDefault()
  }

  function onKeyDown(e: KeyboardEvent): void {
    if (e.key === 'Escape' && active) {
      suppressNextClick()
      cleanup()
    }
  }

  function cleanup(): void {
    window.clearTimeout(longPress)
    if (scrollFrame) cancelAnimationFrame(scrollFrame)
    active = false
    ghost?.remove()
    ghost = null
    document.body.style.userSelect = ''
    dragState.occurrence = null
    dragState.overDate = null
    window.removeEventListener('pointermove', onPointerMove)
    window.removeEventListener('pointerup', onPointerUp)
    window.removeEventListener('pointercancel', cleanup)
    window.removeEventListener('keydown', onKeyDown)
    document.removeEventListener('touchmove', onTouchMove)
    source.removeEventListener('contextmenu', onContextMenu)
  }

  window.addEventListener('pointermove', onPointerMove)
  window.addEventListener('pointerup', onPointerUp)
  window.addEventListener('pointercancel', cleanup)
  window.addEventListener('keydown', onKeyDown)
  document.addEventListener('touchmove', onTouchMove, { passive: false })
  source.addEventListener('contextmenu', onContextMenu)
}

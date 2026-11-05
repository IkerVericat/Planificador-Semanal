# Frontend · Vue 3 PWA

Lee primero el `CLAUDE.md` de la raíz y **mira `docs/mockup.png`** antes de construir o cambiar una pantalla.

## Convenciones

- Vue 3 con `<script setup lang="ts">` y Composition API. TypeScript estricto, sin `any`.
- Vite, Vue Router, Pinia, TailwindCSS. PWA con `vite-plugin-pwa` (manifest, iconos, `display: standalone`).
- Estructura:
  - `src/views/` pantallas (`LoginView`, `RegisterView`, `ForgotPasswordView`, `PlannerView` con las subvistas `WeekView`, `MonthView`, `YearView`)
  - `src/components/` piezas reutilizables (`TaskRow`, `SegmentedControl`, `CategoryChip`, `PeriodNav`, `FabButton`, `TaskForm`)
  - `src/stores/` Pinia (`auth`, `planner`, `categories`)
  - `src/api/` cliente HTTP único (fetch envuelto) que añade `Authorization: Bearer` y, ante un 401, borra el token y manda a `/login`
  - `src/types/api.ts` tipos que reflejan el contrato de la API
  - `src/utils/dates.ts` formato de fechas y horas
- URL de la API en `VITE_API_URL`. El token se guarda en `localStorage` (clave `planner_token`).
- Fechas: usa `date-fns` con locale `es` (o `Intl`), nunca cálculos a mano con milisegundos. La semana empieza en lunes.
- Tests de componentes y utilidades con Vitest + Vue Test Utils.

## Diseño (mobile first y responsive)

- Diseña primero para 375 px de ancho (el mockup) y amplía con los prefijos de Tailwind: `md:` (tablet, ≥ 768 px) y `lg:` (ordenador, ≥ 1024 px).
- Ancho del contenido: `max-w-md` en móvil, `md:max-w-3xl`, `lg:max-w-6xl`. Las pantallas de acceso son una tarjeta (`card`) centrada desde `md:`.
- En ordenador:
  - Cabecera en una fila: título, selector Semana / Mes / Año, botón "+ Nueva tarea" y perfil. El FAB solo existe por debajo de `lg:`.
  - Semana: los 7 días en columnas (`lg:grid-cols-7`); en tablet, 2 columnas. En las columnas, `TaskRow` lleva `stack-on-desktop`: un solo bloque con la hora encima del título.
  - Mes: calendario a la izquierda y lista del día seleccionado a la derecha.
  - Año: 4 columnas de minicalendarios en tablet y 6 en ordenador.
  - La hoja inferior (`BottomSheet`) se convierte en una ventana centrada desde `md:`.
- Fuente: Inter. Paleta **crema / oscuros**, definida como tokens en `@theme` de `src/assets/main.css` (no escribas colores a mano en los componentes; usa los tokens):
  - Fondo general crema `surface` (`#F4EFE6`); tarjetas, campos y hojas marfil `card` (`#FBF8F2`) con `rounded-2xl` y borde `line`.
  - Color primario (botones, día de hoy/seleccionado, FAB, enlaces, foco): carbón cálido `primary` (`#2B2824`), con texto crema `on-primary` encima. `primary-soft` (arena) para fondos suaves y la pista del control segmentado; `accent` para el "Deshacer" sobre fondo oscuro.
  - Texto `ink` y secundario `muted`; mapa de calor `heat-1` / `heat-2`.
  - El `theme-color` de `index.html` y del manifest PWA es el crema del fondo.
- Colores de categoría (vienen de la API, pero estos son los de por defecto): Fijo `#C9CDF2`, Urgente `#EB775A`, Importante `#8DC63F`, Nueva `#F4B942`. El texto encima es oscuro.
- Fila de tarea (`TaskRow`): bloque pequeño con la hora (`9 AM`, `7:30 PM`) + bloque ancho con el título en MAYÚSCULAS y negrita, ambos del color de la categoría, `rounded-lg`.
- Selector Semana / Mes / Año: control segmentado sobre `primary-soft`, la opción activa en `card` con sombra.
- Navegación de periodo: botones `‹` `›` circulares con el título en medio (`12 – 18 de octubre`, `Octubre 2026`, `2026`).
- Mes: puntos de color por categoría bajo cada día; al tocar un día se muestra su lista debajo.
- Año: 12 minicalendarios en 3 columnas; intensidad del día según número de tareas (0, 1–2, 3+), leyenda "Menos … Más tareas"; mes actual con borde primario. Tocar un mes abre la vista Mes.
- FAB "+" circular, primario, fijo abajo a la derecha, abre `TaskForm` en una hoja inferior (bottom sheet).
- Objetivos táctiles de al menos 44 px, `focus-visible` en todo lo interactivo, `aria-label` en botones de solo icono.

## Interacción

- **Arrastrar y soltar** (`composables/useTaskDrag.ts`): Pointer Events sin librerías; con ratón al mover 6 px, con el dedo tras una pulsación larga de 350 ms. Los destinos llevan `data-drop-date="YYYY-MM-DD"`. Una tarea suelta cambia su `task_date`; una recurrente mueve solo ese día (`moved_to_date`). Alternativa sin arrastrar: "Mover solo este día a…" en el formulario.
- **Cambios optimistas y deshacer** (`stores/planner.ts`): mover, completar, quitar un día y borrar se ven al instante (parches que se descartan al recargar o se revierten si la API falla) y muestran `UndoToast` con "Deshacer" durante 5 s. Usa siempre esas acciones del store, no `tasksApi` directamente desde las vistas.
- **Completar con un toque**: círculo a la derecha de cada `TaskRow`.
- **Atajos** (solo con teclado físico, `PlannerView`): `N` nueva tarea, `←` `→` periodo, `T` hoy, `1` `2` `3` vistas, `Ctrl/Cmd+Z` deshacer. Se listan en la hoja de perfil en ordenador.
- **Deslizar** (`composables/useSwipe.ts`): en táctil, izquierda/derecha cambia de periodo. Botón "Hoy" en `PeriodNav` cuando el periodo visible no incluye hoy.

## Despliegue

Vercel o Cloudflare Pages (por decidir): es una SPA estática (`npm run build` → `dist/`). Hace falta reescribir todas las rutas a `index.html`.

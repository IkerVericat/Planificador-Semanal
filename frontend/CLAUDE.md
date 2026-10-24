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

## Diseño (mobile first)

- Diseña para 375 px de ancho; en pantallas grandes, contenido centrado con `max-w-md`. Nada de layouts de escritorio salvo que se pida.
- Fuente: Inter. Fondo general gris azulado muy claro (`#F5F6FB`), tarjetas blancas con `rounded-2xl` y borde sutil.
- Color primario (botones, día seleccionado, FAB, enlaces): índigo `#4B4BD8` aprox. → define `primary` en `tailwind.config`.
- Colores de categoría (vienen de la API, pero estos son los de por defecto): Fijo `#C9CDF2`, Urgente `#EB775A`, Importante `#8DC63F`, Nueva `#F4B942`. El texto encima es oscuro.
- Fila de tarea (`TaskRow`): bloque pequeño con la hora (`9 AM`, `7:30 PM`) + bloque ancho con el título en MAYÚSCULAS y negrita, ambos del color de la categoría, `rounded-lg`.
- Selector Semana / Mes / Año: control segmentado sobre fondo gris, la opción activa en blanco con sombra.
- Navegación de periodo: botones `‹` `›` circulares con el título en medio (`12 – 18 de octubre`, `Octubre 2026`, `2026`).
- Mes: puntos de color por categoría bajo cada día; al tocar un día se muestra su lista debajo.
- Año: 12 minicalendarios en 3 columnas; intensidad del día según número de tareas (0, 1–2, 3+), leyenda "Menos … Más tareas"; mes actual con borde primario. Tocar un mes abre la vista Mes.
- FAB "+" circular, primario, fijo abajo a la derecha, abre `TaskForm` en una hoja inferior (bottom sheet).
- Objetivos táctiles de al menos 44 px, `focus-visible` en todo lo interactivo, `aria-label` en botones de solo icono.

## Despliegue

Vercel o Cloudflare Pages (por decidir): es una SPA estática (`npm run build` → `dist/`). Hace falta reescribir todas las rutas a `index.html`.

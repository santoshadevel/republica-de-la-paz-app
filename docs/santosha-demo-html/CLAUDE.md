# Proyecto Santosha · República de la Paz

## Stack objetivo (tener en cuenta en TODO lo que diseñemos)
El sitio se implementa en **Laravel + Livewire**. Para el front usar:
- **Tailwind CSS** (vía Vite, ya incluido en Laravel 11+). No usar Bootstrap.
- **Alpine.js** (viene con Livewire) para interacciones: acordeones, modales, tabs, menú móvil.
- **Blade / componentes Livewire** para las vistas.

Diseñar pensando en que se traduce a clases Tailwind (los diseños entregados usan estilos utility-like inline, ~1:1 a Tailwind). Interacciones simples de UI → Alpine (`x-data`, `x-show`, `@click`).

## Requisito permanente
- **Mobile-first / responsive** siempre. Menús de navegación con hamburguesa real en mobile (no un nav colapsado/scroll horizontal). Breakpoints estilo Tailwind (`sm md lg`).

## Identidad de marca
- Tipografía: **General Sans** (Fontshare).
- Paleta: terracota `#bf693e`, arena `#ebbc88`, oliva `#636b2f`, tierra `#8e4629`; fondo crema `#f4ecdf`, tinta `#43301f`.
- Estética: cálida, orgánica, comunidad, naturaleza, sofisticación tranquila. Evitar fitness / clínico / corporativo / demasiado esotérico.
- Assets de marca extraídos en `assets/` (logo, isotipo, patrón). Fotografía: natural, luz cálida, tonos tierra.
- Voz: "República de la Paz" (La República en vez de "Sobre Nosotros", Ciudadanos, Asamblea, etc.).

## Archivos
- `Santosha Landing.dc.html` — landing principal.
- `Santosha Actividad.dc.html` — landing genérica de venta de una actividad/contenido (duplicar y editar por evento).

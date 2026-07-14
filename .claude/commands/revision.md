---
description: Revisión de punta a punta de los cambios de la rama — bugs, vulnerabilidades, queries N+1 y queries pesados (Laravel 13 + Filament 5)
argument-hint: "[base git opcional, ej. main o HEAD~3] [--fix]"
allowed-tools: Bash(git diff:*), Bash(git status:*), Bash(git log:*), Bash(git merge-base:*), Read, Grep, Glob
---

Sos un revisor senior de Laravel 13 + Filament 5 para el ERP **Santosha · República de la Paz**.
Revisá de **punta a punta** los cambios de la rama actual y reportá hallazgos concretos y accionables.

## Alcance del diff

- Base de comparación: `$1` si viene dado; si no, la rama principal (`main`) vía `git merge-base`.
- Cambios sin commitear (working tree + staged) SIEMPRE se incluyen.

Contexto para orientar la revisión:

- Rama y estado: !`git status -sb`
- Merge-base con main: !`git merge-base HEAD main 2>/dev/null || echo "sin main"`
- Diff vs main (rango): !`git diff main...HEAD --stat 2>/dev/null | tail -40`
- Diff sin commitear: !`git diff --stat`

Leé el diff completo con `git diff main...HEAD` y `git diff` (working tree). No revises archivos
que no cambiaron salvo que necesites contexto para entender un cambio.

## Reglas del proyecto que son CRITERIO DE FALLA (INNEGOCIABLES)

Estas violaciones son bugs de arquitectura y deben reportarse como **alta prioridad**:

1. **Escrituras solo en el modelo.** Cualquier `create/update/delete/save/insert/upsert/increment/
   decrement/sync` fuera de un modelo Eloquent (en Resources de Filament, controllers, services,
   actions, seeders, commands, jobs, componentes Livewire o **Blade**) es una violación. Los demás
   componentes deben llamar métodos del modelo (`$student->consumeCredit(...)`), no armar el query.
2. **Nada de queries en `.blade`.** Ni lecturas (`Model::where(...)`) ni escrituras. Las vistas
   reciben todo ya resuelto.
3. **White-label / sin hardcodear:** nada específico de Santosha/Paraguay ni moneda fija. Dinero =
   **enteros en unidad mínima** + `currency_code`; nunca una constante de Guaraníes en dominio.
   Campos genéricos (`identity_number`, no `cedula`).
4. **Lógica de negocio con tiempo** (saldo, cancelaciones, cupos) en services/actions, no en
   Resources ni controllers. La mutación final la hace el modelo.

## Qué buscar (foco Laravel/Filament)

### A. Bugs de correctitud
- Lógica invertida, off-by-one, nullables no manejados, early-return faltante.
- Reglas de negocio mal implementadas vs SPEC/REQUISITOS (ej: cancelación grupal > 1h reintegra
  saldo; individual < 24h cobra 50%).
- Casts / enums / fechas mal usados; timezone; comparaciones de dinero en distinta unidad.
- Estados imposibles, transacciones sin `DB::transaction`, **concurrencia** en descuento de cupos
  (falta de lock: `lockForUpdate`), condiciones de carrera al consumir saldo.

### B. Vulnerabilidades
- **Mass assignment**: `$fillable`/`$guarded` flojos, `->fill($request->all())`, `forceFill`.
- **Autorización**: falta de Policy/Gate, `student` accediendo a datos de otros, Resources sin
  `canViewAny/authorize`, endpoints/portal sin scope al usuario logueado (IDOR).
- **SQL injection**: `whereRaw/DB::raw/selectRaw` con interpolación de input.
- **XSS**: `{!! !!}` en Blade con datos de usuario; salidas sin escapar.
- Secrets hardcodeados, datos sensibles en logs, `.env` filtrado, credenciales.
- Validación faltante en formularios/acciones; subida de archivos sin validar tipo/tamaño.

### C. Queries repetidos (N+1)
- Relaciones accedidas dentro de loops / columnas de tabla Filament / vistas Blade sin `with()`
  eager loading. En Filament, columnas de relación sin `->relationship()` o sin `modifyQueryUsing`.
- `whereHas` dentro de iteraciones; `count()`/`exists()` por fila.
- Falta de `->with()`, `->withCount()`, `->load()` donde corresponde.

### D. Queries pesados
- `select *` innecesario cuando se necesitan pocas columnas; traer colecciones enteras a memoria
  para filtrar en PHP en vez de en DB.
- Filtros/orden sobre columnas **sin índice**; `LIKE '%...%'` en columnas grandes.
- Ausencia de paginación en listados grandes; `get()` donde debería `cursor()`/`chunk()`.
- Agregaciones en PHP que deberían ser SQL (`sum/count/group by`).
- Índices/foreign keys faltantes en migraciones nuevas.

## Cómo reportar

Agrupá por severidad. Para cada hallazgo:

- **Severidad**: 🔴 Alta (bug real, vulnerabilidad, violación INNEGOCIABLE) · 🟡 Media (riesgo /
  N+1 / query pesado) · 🔵 Baja (mejora, estilo).
- **Ubicación**: `archivo:línea` en formato markdown clickable.
- **Problema**: qué está mal, en una frase.
- **Escenario de falla**: input/estado concreto → resultado incorrecto (para bugs y vulns).
- **Fix sugerido**: mínimo y concreto (snippet si ayuda).

Si el diff está limpio en alguna categoría, decilo explícitamente. No inventes hallazgos para
llenar; preferí pocos hallazgos de alta confianza. Ordená de más grave a menos.

Si se pasó `--fix` en los argumentos ($ARGUMENTS), aplicá al working tree solo los hallazgos 🔴 y
🟡 de alta confianza, respetando la Regla #1 (la escritura final va en el modelo), y listá al final
qué cambiaste y qué dejaste para revisión manual.

## Nota

Para una revisión de seguridad más profunda existe `/security-review` (nativa), y para el motor de
review general `/code-review` (con niveles y `ultra` multi-agente). Este comando es el pase
integral con foco en el stack y las reglas de Santosha.

# Módulo Membresías y Pases — diseño detallado (Fase 4)

> **Fuente de verdad del diseño de este módulo.** Complementa [REQUISITOS.md](REQUISITOS.md)
> (qué pide el cliente) y [../SPEC.md](../SPEC.md) (plan de fases). Convenciones en
> [../CLAUDE.md](../CLAUDE.md). Código en inglés; dominio/UI en español.

## 1. Objetivo

Modelar la **venta de membresías/pases** y el **saldo de prácticas** de cada alumno, de forma que
un solo evento (vender un pase) habilite reservas, actualice el CRM y registre el ingreso contable.
Ref. PDF: "Sistema de Membresías", "Descuento Automático de Prácticas", "Panel Administrativo →
Alumnos (agregar/descontar prácticas manualmente, asignar/modificar pases)".

## 2. Decisión de arquitectura (Regla #0 — comunidad primero)

Se evaluaron paquetes de la comunidad; **se eligió modelo propio liviano** sobre el `MembershipPlan`
ya existente:

- **`laravelcm/laravel-subscriptions`** (fork de rinvex) — _descartado_. Compatible con Laravel 13,
  pero trae su propio `Plan`/`Feature`/`Subscription` que **duplicaría** nuestro `MembershipPlan`
  (con cast `Money`, JSON `rules`, cobertura por actividad, Filament, seeder y tests ya hechos). Su
  modelo de uso es **por período con reset**; el nuestro es un **pool fijo que vence**. La lógica
  difícil (reintegro por ventana horaria, ilimitado que no descuenta, cobertura por actividad) la
  escribimos igual. Costo > beneficio.
- **`laravel/cashier`** (Stripe) — _descartado_. Es facturación recurrente vía **Stripe**
  (`stripe/stripe-php`). En Paraguay los pagos son manuales/Bancard/efectivo/transferencia y el pase
  es prepago con pool de créditos, no una suscripción de tarjeta. Acoplaría todo a Stripe
  (anti white-label). No aplica al dominio.
- **Elegido: modelo propio** — dominio chico y a medida: `StudentMembership` + `CreditMovement`
  (ledger), reutilizando `MembershipPlan`. Encaja exacto con el PDF y el white-label.

> Si más adelante se agrega pasarela de pago online recurrente, se re-evalúa Cashier **solo** para el
> cobro, sin acoplar el dominio de saldo.

## 3. Capas y conceptos

| Capa | Modelo | Rol |
|------|--------|-----|
| Catálogo (existe, Fase 2) | `MembershipPlan` | Plantilla: precio, `rules` (credits, unlimited, validity_days, cancellation, included_types), cobertura de actividades. |
| Instancia vendida (Fase 4) | `StudentMembership` | Lo que **un alumno compró**: snapshot de reglas + vigencia + precio pagado + estado. |
| Ledger de saldo (Fase 4) | `CreditMovement` | Cada cambio de saldo (venta, consumo, reintegro, ajuste, expiración). Saldo = suma de movimientos. Auditable e historizable. |

**Por qué snapshot:** al vender se copian `credits_total`, `is_unlimited`, `price_paid`,
`currency_code` y `validity_days` a la `StudentMembership`. Así, si mañana cambia el precio o las
reglas del plan, **las ventas pasadas no se alteran**.

**Por qué ledger (y no solo un contador):** el PDF pide historial, reintegros y ajustes manuales.
Un ledger de movimientos con signo da saldo auditable (`SUM(amount)`), explica cada cambio y soporta
"agregar/descontar prácticas manualmente" sin perder trazabilidad.

## 4. Modelo de datos

### `student_memberships`
| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | |
| student_id | FK students | cascade |
| membership_plan_id | FK membership_plans | nullOnDelete (los snapshots preservan los datos) |
| credits_total | unsignedInteger nullable | null = ilimitado (snapshot) |
| is_unlimited | boolean | snapshot |
| price_paid | unsignedBigInteger | minor unit (cast Money) — snapshot |
| currency_code | string | snapshot (white-label) |
| starts_at | date | inicio de vigencia |
| ends_at | date | `starts_at + validity_days` (snapshot) |
| status | string (enum) | `active` \| `expired` \| `cancelled` — materializado para queries y scheduler |
| notes | text nullable | |
| timestamps, softDeletes | | |

### `credit_movements`
| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | |
| student_membership_id | FK | cascade |
| type | string (enum) | `sale` \| `consumption` \| `refund` \| `manual_adjust` \| `expiration` |
| amount | integer (con signo) | + venta/reintegro/ajuste+, − consumo/ajuste− |
| reason | string nullable | motivo (obligatorio en ajuste manual) |
| booking_id | FK nullable | se llena en Fase 5 (enlaza movimiento ↔ reserva) |
| created_by | FK users nullable | quién hizo el ajuste manual |
| timestamps | | |

**Saldo disponible** = `SUM(credit_movements.amount)` de la membresía. Ilimitada → infinito
(las reservas se registran pero no generan movimientos que afecten saldo).

## 5. Reglas de negocio

- **Venta** (`SellMembership`): crea `StudentMembership` con snapshots (`starts_at = hoy`,
  `ends_at = hoy + validity_days`, `status = active`) + `CreditMovement{type: sale, amount: credits_total}`
  (sin movimiento de saldo si es ilimitada). **Hook contable** (Fase 7): registrar `Transaction`
  de ingreso (categoría Membresías, método de pago, centro de costo).
- **Vigencia:** activa si `status = active` **y** `hoy <= ends_at`. Un command diario
  (`ExpireMemberships`) marca `expired` las vencidas; además hay un scope `active()` que filtra por
  fecha para lecturas.
- **Saldo:** `SUM(amount)`; sin saldo no se puede reservar (salvo ilimitada). Regla del PDF.
- **Consumo** (Fase 5, al reservar una **práctica grupal**): `ConsumeMembershipCredit` →
  `CreditMovement{type: consumption, amount: -1, booking_id}`. Ilimitada: registra la reserva sin
  afectar saldo. Verifica membresía vigente + saldo > 0 + que el plan **cubra** la actividad
  (`MembershipPlan::coversActivity`).
- **Reintegro** (Fase 5, cancelar práctica grupal **>1 h** antes): `RefundMembershipCredit` →
  `CreditMovement{type: refund, amount: +1}`. Con <1 h o no-show: se consume (sin movimiento).
- **Ajuste manual** (Fase 4, admin — "agregar/descontar prácticas manualmente"): `AdjustMembershipCredits`
  → `CreditMovement{type: manual_adjust, amount: ±n, reason, created_by}`.
- **Membresía actual** de un alumno = la `active` vigente. Si hubiera varias, la de `ends_at` más
  lejano (normalmente hay una sola activa a la vez).

> **Aclaración de alcance del pool:** el saldo de créditos aplica solo a **prácticas grupales**
> (`included_types = group_class`). Los **acompañamientos individuales** se pagan aparte (no
> consumen créditos); su regla de cancelación 24 h/50 % es un **cobro**, no un movimiento de saldo, y
> vive en Agendamientos/Contabilidad (Fase 6/7).

## 6. Ciclo de vida (estado)

```
        vender            hoy > ends_at
  (none) ──────► active ───────────────► expired
                   │
                   │ cancelar (admin)
                   └──────────────────► cancelled
```

## 7. Integraciones

- **CRM** (Fase 3/4): la ficha del alumno muestra **membresía actual**, **saldo** (disponibles/
  usadas/restantes) e **historial de compras** (relation manager de `StudentMembership`).
- **Agendamientos** (Fase 5): consume/reintegra vía las Actions; `booking_id` enlaza cada movimiento
  con su reserva.
- **Contabilidad** (Fase 7): `SellMembership` es el **único** punto donde se registrará el ingreso.

## 8. Servicios / Actions (`app/Actions`, reutilizables por Filament y la futura API)

| Action | Fase | Qué hace |
|--------|------|----------|
| `SellMembership` | 4 | Vende un plan a un alumno (snapshots + movimiento `sale`; hook contable). |
| `AdjustMembershipCredits` | 4 | Suma/resta créditos manualmente con motivo. |
| `ExpireMemberships` (command) | 4 | Marca `expired` las vencidas (scheduler diario). |
| `ConsumeMembershipCredit` | 5 | Descuenta 1 al reservar (o registra sin descuento si ilimitada). |
| `RefundMembershipCredit` | 5 | Reintegra 1 al cancelar dentro de la ventana. |

## 9. Alcance de Fase 4 (este entregable)

**Incluye:** modelos `StudentMembership` + `CreditMovement` (+ enums), migraciones, Actions
`SellMembership` y `AdjustMembershipCredits`, command `ExpireMemberships`, accesores de saldo/vigencia,
UI Filament (vender/asignar pase y ver saldo+historial desde la ficha del alumno), factory y tests.

**Fuera (fases siguientes):** consumo/reintegro reales por reserva (Fase 5); asiento contable de la
venta (Fase 7); notificación "pase próximo a vencer / pocas prácticas" (Fase 10).

## 10. White-label

- `price_paid` + `currency_code` como **snapshot**; nada de Guaraníes hardcodeado.
- Créditos, ilimitado y vigencia salen del `MembershipPlan` (datos), nunca constantes en código.

## 11. Extensión futura — membresías que habilitan contenido/accesos digitales (backlog)

> **Idea nueva, no proveniente del PDF.** Anotada para no perderla; se re-evalúa como fase
> aparte (probablemente junto a la webapp del alumno / landing, Fase 9+). Requiere confirmar
> alcance con el usuario antes de construir.

Además del **pool de créditos de práctica** (lo modelado arriba), una membresía debe poder
**habilitar entitlements digitales**, p. ej.:

- **Contenido / blog:** posts o secciones exclusivas para miembros.
- **Series de videos (cursos):** biblioteca de cursos on-demand desbloqueada por el plan.
- **Accesos a links:** URLs de llamadas/encuentros online (Zoom/Meet), salas o recursos
  gated por membresía.

**Enfoque tentativo (comunidad primero — Regla #0, a evaluar cuando se retome):**

- Modelar los beneficios como **entitlements** en el `MembershipPlan` (no hardcodear tipos):
  extender el JSON `rules`/cobertura del plan con un set de *features* (`content`, `course`,
  `call_link`, …) en vez de columnas nuevas por cada tipo → mantiene white-label y no ata el
  dominio a Santosha.
- La **autorización** de acceso se resuelve por Policy/Gate: "¿la membresía vigente del alumno
  incluye la feature X / el curso Y?" — reutilizable por Filament y por la futura API/MCP.
- El **contenido** (cursos/videos/posts) es un dominio propio a diseñar (¿modelo `Course`,
  `Lesson`, `Resource`?); antes de construirlo, **investigar paquetes** de LMS/contenido para
  Laravel/Filament y registrar la decisión aquí.
- A diferencia del pool de créditos, estos accesos **no se consumen** (son binarios: habilitado
  mientras la membresía esté vigente), así que no generan `CreditMovement`.

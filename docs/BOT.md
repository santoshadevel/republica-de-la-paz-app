# BOT.md — Agente de agendamiento/coordinación (Santosha ERP)

> **Estado: BORRADOR.** Fuente de verdad de *qué hace el bot AI*, análoga a
> [REQUISITOS.md](REQUISITOS.md) para el negocio. Trazable: cada capacidad mapea a una
> Action/Service del dominio (✅ existe · 🟡 parcial · ❌ falta). Se construye **tras
> Fase 5/6** (cuando el dominio de agenda está listo); ver [CLAUDE.md](../CLAUDE.md) §
> "Arquitectura white-label / API".

## 1. Propósito y rol

El bot cubre **~99% de un rol de agendamiento/coordinación** (recepción conversacional):
atiende a alumnos (y a staff) en lenguaje natural para **consultar, reservar, cancelar,
reprogramar e informar** sobre la agenda, las membresías y los eventos — apoyándose en la
misma lógica de dominio que usa el panel Filament. **No** reemplaza a Administración ni
toca contabilidad/configuración.

Principio rector: **el bot orquesta, el dominio decide.** El bot traduce intención →
llamada a una Action/Service; las reglas (saldo, cupo, cancelación, disponibilidad,
concurrencia) viven en el modelo/servicio y son la única fuente de verdad. El bot nunca
arma queries ni replica reglas (coherente con la Regla #1).

## 2. Alcance

### Hace
- **Maneja la agenda de TODO el sistema** — de alumnos **y** de profesionales: agregar,
  sacar, mover y **confirmar** prácticas grupales, acompañamientos y eventos (crear/cancelar
  ocurrencias, asignar/quitar alumnos, registrar asistencia, ajustar horarios de profes).
- Responde sobre la agenda: qué hay, cuándo, con quién, cupos, disponibilidad.
- **Reserva / cancela / reprograma** en nombre del alumno.
- **Vende y concreta la venta** de pases/membresías (incluye el cobro).
- Consulta el **estado de la membresía**: saldo de prácticas, vigencia, cobertura.
- **Propone huecos** libres de un profesional (según disponibilidad + reservas).
- **Informa/recuerda**: confirmaciones, recordatorios, avisos de cambios (vía Fase 10).

> El alcance de escritura depende del **rol** con que actúa el bot (§6): operando para
> `admin`/`receptionist` gestiona la agenda de todos y cobra; operando para un `student` se
> limita a su propia agenda y a comprarse un pase.

### No hace (escala a humano)
- Egresos, reembolsos, transferencias o **contabilidad** general más allá de registrar el
  ingreso de una venta de pase que él mismo concreta.
- Modificar **configuración** (salas, actividades, planes, honorarios, permisos).
- **Waivers** de reglas: forzar cupo lleno, saltar la ventana de cancelación, condonar la
  penalidad de <24 h, extender vigencia vencida.
- Editar/borrar datos de otros alumnos o acceder a información fuera del permiso del rol.
- Decisiones ambiguas o conflictivas que la política no resuelve → **handoff** a staff.

## 3. Reglas de negocio que el bot debe respetar (no reimplementar)

Vienen del dominio; el bot solo las **invoca** y comunica el resultado:
- Práctica grupal: reservar descuenta 1 del saldo; cancelar **>1 h antes** reintegra.
- Acompañamiento: cancelar con **<24 h** cobra 50 % (penalidad, no reembolso automático).
- Membresía: reservar requiere pase **vigente**, con **saldo** y que **cubra** la actividad.
- Disponibilidad del profesional: no agendar fuera de sus bloques/horarios.
- Cupos con control de **concurrencia** (lock) — el bot nunca "adivina" el cupo, lo consulta.

## 4. Capacidades → mapa al dominio

Intención del usuario ⇒ pieza de dominio que la resuelve.

### 4.1 Consulta (lectura)
| Intención | Dominio | Estado |
|---|---|---|
| "¿Qué clases hay esta semana?" | `CalendarService::eventsBetween()` | ✅ |
| "¿Qué tengo agendado?" | `StudentAgendaService::for()` (upcoming/past) | ✅ |
| "¿Cuánto saldo me queda?" | `StudentMembership::creditsRemaining()` / `isCurrentlyActive()` / `hasAvailableCredit()` | ✅ |
| "¿Está libre Mateo el martes 16 h?" | `Practitioner::isAvailableAt()` | ✅ |
| "¿Cuántos lugares quedan?" | `ScheduledSession::seatsAvailable()` / `isFull()` | ✅ |
| "¿Cuál es el próximo hueco de Mateo?" | `Practitioner::openingHours()->nextOpen()` (sin considerar reservas) | 🟡 |
| "Dame los horarios libres para un acompañamiento" | `findAvailableSlots()` (disponibilidad − reservas) | ❌ |

### 4.2 Agendamiento (escritura, vía Actions)
| Intención | Dominio | Estado |
|---|---|---|
| "Reservame el Yoga del miércoles" | `BookSession::execute(Student, ScheduledSession)` | ✅ |
| "Cancelá mi reserva" | `CancelBooking::execute(Booking)` | ✅ |
| "Agendame un acompañamiento con Mateo" | `BookAppointment::execute(Student, Appointment)` | ✅ |
| "Cancelá mi acompañamiento" | `CancelAppointment::execute(Appointment)` | ✅ |
| "Inscribime al taller del sábado" | `RegisterForEvent::execute(Student, Event)` | ✅ |
| "Cancelá mi inscripción al evento" | `CancelEventRegistration::execute(EventRegistration)` | ✅ |
| "Movelo al jueves a las 10" (reprogramar) | `RescheduleBooking` / `RescheduleAppointment` | ❌ |
| Detección de conflictos al agendar | `SchedulingService::findConflict()` | ✅ |

### 4.3 Membresías
| Intención | Dominio | Estado |
|---|---|---|
| "¿Qué pases hay y qué incluyen?" | `MembershipPlan` (`rules`: `unlimited`/`credits`/`validity_days`/`included_types`) | ✅ |
| "Comprame el Pase Comunidad" (con cobro) | `SellMembership::execute(...)` + pasarela de pago | 🟡 (falta pasarela de pago) |
| Consumo/reintegro de créditos | `ConsumeMembershipCredit` / `RefundMembershipCredit` (los invocan las Actions de reserva) | ✅ |

### 4.4 Gestión de agenda del sistema (staff)
| Intención | Dominio | Estado |
|---|---|---|
| "Creá una clase de Yoga el lunes 9 h" | `ScheduledSession::schedule()` | 🟡 (falta Action con validación de conflicto/disponibilidad) |
| "Programá el horario semanal recurrente" | `SchedulingService::generateRecurringSessions()` | ✅ |
| "Cancelá la sesión del miércoles" | cancelar `ScheduledSession` (+ avisar inscriptos) | 🟡 |
| "Confirmá asistencia" (grupal/evento/individual) | `MarkAttendance` / `MarkEventAttendance` / `CompleteAppointment` | ✅ |
| "Cargá la disponibilidad de Mateo" | `Practitioner` availabilities/exceptions | 🟡 (CRUD listo; falta Action para el bot) |

### 4.5 Comunicación
| Intención | Dominio | Estado |
|---|---|---|
| Confirmaciones / recordatorios / avisos de cambio | Notificaciones (Fase 10) | ❌ |

## 5. Superficie técnica (propuesta, a construir)

Tres capas sobre las mismas Actions/Services:

0. **Canal de mensajería — WhatsApp vía API de [2chat](https://2chat.co).** Es el punto de
   entrada/salida conversacional: el bot **escucha** los mensajes entrantes de WhatsApp por
   **webhook** de 2chat y **responde** por su API de envío. Un endpoint propio
   (`POST /api/webhooks/2chat`, verificado por secreto) recibe cada mensaje, resuelve el
   `Student`/usuario por su número de teléfono, arma el contexto y llama al agente; la
   respuesta se manda de vuelta con el cliente de 2chat.
   - **White-label:** 2chat es el proveedor elegido para Santosha, pero el canal se diseña
     detrás de una interfaz genérica (`MessagingChannel`/`WhatsAppGateway`) para poder
     cambiar de proveedor (o sumar otros canales: web, Telegram) sin tocar el dominio.
     La API key y el número de 2chat van por **configuración/env**, nunca hardcodeados.
   - **Pendiente de confirmación (Regla #0):** 2chat es un servicio SaaS externo, no un
     paquete Composer; validar plan/costos y encaje antes de adoptarlo formalmente.
1. **API REST (Sanctum)** — endpoints por recurso (`/api/agenda`, `/api/bookings`,
   `/api/students/{id}/agenda`, `/api/practitioners/{id}/availability`, …). Auth por token;
   permisos por rol (spatie). Los controllers **solo orquestan**.
2. **Servidor MCP** encima de la API — expone *tools* al agente. Set inicial propuesto:
   - `list_agenda(from, to)` → `CalendarService`
   - `student_agenda(student)` → `StudentAgendaService`
   - `check_membership(student)` → `StudentMembership`
   - `find_slots(practitioner, from, to, duration)` → *nuevo* `findAvailableSlots`
   - `book_session(student, session)` → `BookSession`
   - `cancel_booking(booking)` → `CancelBooking`
   - `book_appointment(student, appointment)` → `BookAppointment`
   - `register_event(student, event)` → `RegisterForEvent`

Cada tool devuelve el resultado del dominio (incluidas las excepciones de negocio
`BookingException`/`AppointmentException`/`EventException` como mensajes legibles, para que
el bot los comunique en vez de fallar).

## 6. Autorización

- El bot actúa **en nombre de un usuario** con un rol (`student` o staff). El token acota lo
  que puede ver/hacer; las Policies por módulo siguen aplicando igual que en Filament.
- Un `student` solo accede a **sus** datos y agenda; staff (`receptionist`/`admin`) puede
  operar sobre terceros según permisos.
- Nada específico de Santosha/Paraguay hardcodeado (white-label): moneda, `identity_number`,
  etc. por configuración.

## 7. Principios de interacción

- **Confirmar antes de escribir** acciones con impacto (reservar/cancelar/reprogramar):
  el bot resume "voy a hacer X" y espera OK.
- **Idempotencia / no duplicar**: releer estado antes de actuar; no reintentar a ciegas.
- **Comunicar reglas, no ocultarlas**: si una reserva falla por saldo/cupo/ventana, explicar
  el porqué y ofrecer alternativa (otro horario, comprar pase, lista de espera).
- **Handoff limpio**: ante pago, waiver o ambigüedad, derivar a un humano con el contexto.

## 8. Gaps para habilitar el bot (backlog)

- [ ] `findAvailableSlots(practitioner, rango, duración)` — huecos reales = disponibilidad − reservas.
- [ ] Reprogramación: `RescheduleBooking` / `RescheduleAppointment` (mover ocurrencia respetando reglas).
- [ ] Enforce de disponibilidad también al **crear acompañamientos** (hoy solo en la generación recurrente de sesiones).
- [ ] **Canal WhatsApp vía API de 2chat**: webhook entrante (`POST /api/webhooks/2chat`, verificado por secreto), cliente de envío y resolución de usuario por teléfono; detrás de una interfaz genérica de mensajería (white-label). Confirmar el servicio con el usuario (Regla #0).
- [ ] API REST (Sanctum) + Policies de API por rol.
- [ ] Servidor MCP con el set de tools de §5.
- [ ] Notificaciones (Fase 10) para confirmaciones/recordatorios.
- [ ] (Opcional) Lista de espera para sesiones/eventos llenos.
- [ ] Pago del alumno para autoservicio de compra de pases (hoy `SellMembership` asume pago manual/Recepción).

## 9. Relación con otras fuentes

- Negocio / PDF del cliente: [REQUISITOS.md](REQUISITOS.md).
- Plan por fases: [SPEC.md](../SPEC.md) — **falta una fase explícita de API/MCP/bot** (hoy termina en Fase 10 · Notificaciones).
- Membresías: [MODULO_MEMBRESIAS.md](MODULO_MEMBRESIAS.md).

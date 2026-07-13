# Requisitos — Santosha · República de la Paz (transcripción del PDF)

> **Fuente de verdad del negocio.** Este documento transcribe el PDF original de requisitos del
> cliente a un checklist trazable. **Nada del PDF debe perderse:** cada requisito vive aquí con su
> fase asignada. Al cerrar una fase, marcar los ítems correspondientes. Si el cliente entrega un PDF
> nuevo, actualizar este archivo (y dejar el PDF binario en `docs/`).
>
> El **plan de fases** y las decisiones técnicas están en [../SPEC.md](../SPEC.md).
> Las **convenciones** (idioma, white-label, comunidad-primero) en [../CLAUDE.md](../CLAUDE.md).
>
> Leyenda de estado: `[ ]` pendiente · `[~]` parcial · `[x]` hecho. Columna **Fase** = dónde se
> implementa (ver SPEC.md).

---

## 0. Necesidades macro (pág. 1)

- [ ] **Landing Page** con info de servicios/horarios + posibilidad de pagar e inscribirse a clases. Ref: asanagroove.com (menos su calendario). — _Fase 9_
- [ ] **Sistema de Agendamientos** de alumnos: clases semanales, sesiones individuales, eventos. Ref calendario: theyogaclubbarcelona.com. Perfil con clases habilitadas que se restan al agendar. — _Fase 5/6_
- [ ] **Sistema Administrativo y Contable**: costos, ingresos, facturación, vencimiento de cuotas, ingresos/egresos por cuenta contable y categoría. — _Fase 7_
- [ ] **CRM** de clientes, alumnos y terapeutas. — _Fase 3_

---

## 1. Landing pública (págs. 1–10)

### 1.1 La República ("Sobre Nosotros")
- [ ] Sección "La República": qué es Santosha, por qué "República de la Paz", importancia de la comunidad, visión de coexistencia de disciplinas. — _Fase 9_

### 1.2 Constitución de la República
- [ ] Publicar el texto de la Constitución (Artículos 1–5 + Disposición final). Es copy de landing, no lógica. — _Fase 9_

### 1.3 Sesiones semanales (2 salas en simultáneo)
- [ ] Vista de horario semanal. **El calendario debe soportar 2 salas en paralelo por franja.** — _Fase 5_
- [x] Las salas pueden ser **físicas o virtuales** (`Room.type`; las virtuales llevan `meeting_url`). Seed inicial: Sala Principal, Sala Secundaria, Consultorio, Sala Virtual.

Horario de referencia (dato de negocio a sembrar, no hardcodear):

| Hora  | Lunes | Martes | Miércoles | Jueves | Viernes | Sábado |
|-------|-------|--------|-----------|--------|---------|--------|
| 8:30  | Hatha Vinyasa | | Hatha Vinyasa | | Hatha Vinyasa | |
| 10:00 | | | | | | clases enfocadas |
| 11:15 | | Hatha Vinyasa | | Hatha Vinyasa | | |
| 12:30 | Hatha | Yogafitness | Hatha | Yogafitness | Hatha | |
| 14:30 | | | | | | clases enfocadas |
| 17:30 | Vinyasa | Aero | Vinyasa | Aero | Vinyasa | |
| 18:45 | Slow-yin-restaurativo | Hatha-vinyasa | Hatha-vinyasa | Hatha-vinyasa | Slow-yin-restaurativo | |

Tipos de yoga mencionados: Hatha, Vinyasa, Hatha Vinyasa, Yogafitness, Aero, Slow/Yin/Restaurativo.

- [ ] Cada práctica del calendario muestra: día + horario + actividad + facilitador. — _Fase 5_
- [ ] Botón "Reservar" + botón "ver más info" (descripción, duración, intensidad/nivel). — _Fase 5_

### 1.4 Consultas y Acompañamientos Individuales
Especialidades (landing): Reiki, Sound Healing Individual, Medicina Ayurvédica, Masaje Ayurvédico, Fisioterapia, Psicología, Tarot, Diseño Humano.
- [~] Cada especialidad muestra: descripción breve, **profesional responsable**, duración, botón de reserva. — _Fase 6 (dominio + agenda admin listos; vista pública de reserva → Fase 9)_

### 1.5 Referentes de la República (equipo)
- [ ] Perfiles con disciplinas + biografía breve: — _Fase 3 (datos) / Fase 9 (landing)_
  - Eloisa Carmona: Yoga, Meditación, Respiración Consciente, Tarot.
  - Neli Duarte: Yoga, Sound Healing, Reiki, KAP.
  - Magu Venialgo: Yoga, Diseño Humano, Meditación.
- [ ] Sección "Ciudadanos de la República" preparada para sumar futuros profesionales/colaboradores. — _Fase 9_

### 1.6 Membresías y Pases (landing) — precios de referencia
- [ ] **Clase de Prueba Gratuita**: 1 práctica grupal gratis + conocer facilitadores + recorrido. — _Fase 4_
- [ ] **Pase Ciudadano** — 4 prácticas/mes — **Gs 350.000**. Incluye: 4 prácticas grupales, acceso a todas las disciplinas grupales, reserva online, comunidad. — _Fase 4_
- [ ] **Pase Comunidad** — 12 prácticas/mes — **Gs 400.000**. Incluye: 12 prácticas, todas las disciplinas, prioridad en lista de espera, beneficios en eventos seleccionados, comunidad. — _Fase 4_
- [ ] **Membresía República** — ilimitada — **Gs 480.000**. Incluye: acceso ilimitado, prioridad de reserva, descuentos en talleres/asambleas, beneficios en individuales, encuentros exclusivos, acceso preferencial. — _Fase 4_

> Precios sembrados en `database/seeders/PlanSeeder.php` (moneda configurable; Gs = 0 decimales).

### 1.7 Asamblea: Nuestros eventos
- [ ] Espacio para: Workshops, Charlas, Retiros, Círculos, Encuentros especiales. — _Fase 6_

### 1.8 FAQ
- [ ] Bloques de preguntas frecuentes: Reservas, Cancelaciones, Membresías, Sesiones individuales, Primeras clases, Sobre yoga, Terapias de sonido, Terapias alternativas, Reservas/asistencia, Bienestar. (Contenido completo en el PDF pág. 7–9.) — _Fase 9_

### 1.9 Contacto
- [ ] WhatsApp, Instagram, Ubicación/mapa, Formulario de contacto. — _Fase 9_

### 1.10 Dirección creativa
- [ ] Evitar estética: fitness, clínica, corporativa, demasiado esotérica.
- [ ] Transmitir: calidez, comunidad, belleza simple, naturaleza, presencia, bienestar integral, sofisticación tranquila, espiritualidad accesible. — _Fase 9_

---

## 2. Módulo Agendamiento de Alumnos (págs. 10–17)

> **Decisión (ver SPEC.md):** el autoservicio del alumno vive en el **frontend público con login**, no en un panel Filament de alumno.

### 2.1 Perfil del Alumno
- [~] Muestra: nombre, contacto, membresía/pase activo, fecha inicio y **vencimiento** del pase. — _Fase 3/4_ · _Datos + ficha admin listos (`currentMembership`); perfil de cara al alumno en el front público → Fase 9._
- [~] Muestra saldo: prácticas disponibles del mes, utilizadas, restantes. — _Fase 4_ · _Saldo (`creditsRemaining`/`creditsConsumed`) visible en la ficha admin; vista alumno → Fase 9._
- [ ] Historial de reservas, de cancelaciones. — _Fase 5_
- [ ] Historial de pagos _(opcional 2ª etapa)_. — _Futuro_

### 2.2 Reserva de Prácticas Grupales
- [ ] Calendario semanal (ampliable a mes) con columnas por día, filas por horario, navegación entre semanas, responsive. — _Fase 9 (front público; requiere decidir enfoque de calendario)_
- [ ] Cada práctica muestra: nombre, horario, duración, facilitador, sala, cupos disponibles. — _Fase 5_
- [ ] Al hacer clic: descripción, facilitador, **nivel (si aplica)**, cupos, botón "Reservar". — _Fase 5_

> **Nota de diseño — modelo de agenda (Fase 5). "¿Quién dicta la actividad del día X?"**
> El pivote `activity_practitioner` (Fase 3) solo dice **quién *puede*** dictar una actividad
> (especialidad, sin fecha). El facilitador **concreto de un día** vive en la instancia agendada,
> NO en el pivote. Modelar dos niveles:
> 1. **Plantilla recurrente** (horario semanal fijo del PDF, p. ej. "Hatha Vinyasa Lun/Mié/Vie 8:30"):
>    define actividad, sala, hora y **facilitador por defecto**.
> 2. **Ocurrencia con fecha** (`scheduled_session`): generada de la plantilla, con
>    `activity_id`, **`practitioner_id` (quién la dio ese día — admite suplencias)**, `room_id`,
>    `starts_at`/`ends_at`, `capacity`, `status`. El "quién dictó el día X" se guarda **por ocurrencia**.
> Debe soportar **2 salas en simultáneo** por franja. Este `practitioner_id` por ocurrencia es la
> fuente para asistencia, reportes por profesional y **liquidación de honorarios** (Fase 8).

### 2.3 Control de Cupos
- [x] Cupo máximo configurable por práctica (`ScheduledSession.capacity`). — _Fase 5_
- [x] Mostrar lugares disponibles y **bloquear reservas cuando se llena** (con `lockForUpdate` para concurrencia). — _Fase 5_
- [ ] Lista de espera _(opcional 2ª etapa)_. — _Futuro_

### 2.4 Descuento Automático de Prácticas
- [x] Al reservar: verificar pase/membresía vigente, verificar saldo (salvo ilimitada), descontar 1 (`BookSession`). — _Fase 5_
- [x] **Política cancelación grupal:** cancelar **>1 h antes** reintegra saldo + libera cupo; con **<1 h o no-show** se consume (`CancelBooking`, ventana en config/booking.php). — _Fase 5_
- [x] Ilimitadas no descuentan pero **igual registran** reservas, cancelaciones y asistencias. — _Fase 5_

### 2.5 Reserva de Acompañamientos Individuales
Ejemplos: Psicología, Reiki, Sound Healing, KAP, Medicina Ayurvédica, Masaje Ayurvédico, Fisioterapia, Tarot, Diseño Humano, Yoga Terapéutico.
- [~] Al seleccionar: profesional, descripción, duración, agenda disponible, días/horarios libres → confirmar. — _Fase 6_ · _Dominio + reserva admin listos (Appointment slots + BookAppointment); vista/selección del alumno → Fase 9._
- [x] **Cancelación:** hasta 24 h antes sin costo; con <24 h se cobra **50%** (`CancelAppointment`, config/booking.php). — _Fase 6_

### 2.6 Gestión de agendas (individuales)
- [x] Agendas administradas **exclusivamente por admin**: crear/modificar/bloquear horarios, reprogramar, cancelar sesiones (AppointmentResource: estados available/booked/blocked/completed/cancelled). — _Fase 6_
- [ ] Profesionales **no** modifican su agenda; sí ven panel con: próximos agendamientos, historial, info básica del alumno (según permisos). — _Fase 6 (panel de rol profesional + policies → pendiente)_

### 2.7 Reserva de Eventos
- [~] Cada evento muestra: imagen, nombre, fecha, horario, lugar, facilitador(es), descripción, precio (si aplica), cupos, botón "Reservar". — _Fase 6_ · _Event + inscripciones + cupo + facilitadores + imagen (admin) listos; vista/reserva del alumno → Fase 9._

### 2.8 Mis Reservas
- [ ] Próximas reservas (fecha, hora, actividad, facilitador). — _Fase 5_
- [ ] Historial: prácticas grupales, acompañamientos, eventos. — _Fase 5/6_
- [ ] Registro de cancelaciones. — _Fase 5_

### 2.9 Notificaciones Automáticas
- [ ] Confirmación de reserva; recordatorio **24 h y 1 h antes**; aviso de cancelación; aviso de membresía próxima a vencer; aviso de pocas prácticas restantes. — _Fase 10_
- [ ] Canal por definir: email / WhatsApp / ambos. — _Fase 10_

### 2.10 Reglas de Negocio (resumen)
- [~] Solo reservan con pase/membresía vigente; sin saldo no se reserva (salvo ilimitada); no superar cupos; reintegro >1 h; sin reintegro fuera de plazo; **registrar asistencia** siempre. — _Fase 5/6_ · _Grupales completo (Actions + asistencia); individuales → Fase 6._

### 2.11 Panel Administrativo (agendamiento)
- [~] **Alumnos:** crear/editar, historial completo, reservas activas, asistencias, cancelaciones, **agregar/descontar prácticas manualmente**, asignar/modificar pases. — _Fase 3/4_ · _Hecho: crear/editar, asignar pase (vender) y ajuste manual de créditos desde la ficha; reservas/asistencias/cancelaciones → Fases 5/6._
- [~] **Prácticas grupales:** crear prácticas/horarios, modificar, asignar facilitadores, configurar cupos, registrar asistencia, listado de inscriptos. — _Fase 5_ · _Hecho: ScheduledSessionResource (crear/editar sesión, facilitador, sala, cupo, estado) + roster (reservar en nombre, cancelar, asistencia). Pendiente: generador de horario semanal recurrente (plantilla→ocurrencias)._
- [x] **Acompañamientos:** crear/modificar/bloquear agendas, reprogramar, cancelar, ver agenda de todos los profesionales. — _Fase 6_
- [x] **Eventos:** crear, editar, definir cupos, gestionar inscripciones, registrar asistencia. — _Fase 6_
- [ ] **Reportes:** reservas por práctica, asistentes, ocupación, historial por alumno/profesional, estadísticas de uso de pases. — _Fase 8_

---

## 3. CRM (págs. 18–21)

### 3.1 Ficha única del alumno
- [~] Una sola ficha por persona (identidad única por email; `identity_number` opcional). Campos: nombre, apellido, **cédula (identity_number)** y **RUC (tax_id)**, teléfono, email, fecha de nacimiento, **cómo conoció Santosha (acquisition_source)**, **objetivos (goals)**, observaciones (notes), membresía actual. — _Fase 3_ · _Campos listos (incl. tax_id/acquisition_source/goals); "membresía actual" llega con Fase 4._
- [ ] Historiales agregados: compras, clases, sesiones individuales, eventos, pagos. — _Fase 3+_

### 3.2 Membresías conectadas
- [x] Vender membresía dispara automáticamente: registrar ingreso contable, actualizar CRM, habilitar N prácticas, dejar al alumno listo para reservar. Saldo 12→11 al reservar, 11→12 al cancelar a tiempo. — _Fase 4/5/7_ · _`SellMembership` habilita créditos + CRM + registra el ingreso (con método de pago); 12→11 / 11→12 por reserva (Fase 5)._

### 3.3 Agendamientos consultan saldo
- [ ] Con saldo → reserva; sin saldo → "No tienes un pase vigente." — _Fase 5_

### 3.4 CRM + Marketing (segmentación dinámica)
- [~] Motor de filtros/segmentos sobre datos conectados. Ejemplos: "no vienen hace >30 días", "menos de 2 clases disponibles", "hicieron Yoga pero nunca Sound Healing". — _Fase 3/8_ · _Filtros básicos hoy (estado activo, canal de captación); los que dependen de asistencia/saldo llegan con Fases 4–6._

---

## 4. Módulo Administrativo y Contable (págs. 22–32)

### 4.1 Ingresos — clasificación
- [x] Cada ingreso con **categoría + subcategoría + unidad de negocio (centro de costo)**. — _Fase 7_ · _Transaction genérica (income/expense) + Category jerárquica + CostCenter + PaymentMethod._

Categorías de ingreso (a sembrar): **Membresías** (República, Comunidad, Ciudadano, Prueba) · **Acompañamientos** (Psicología, Reiki, KAP, Sound Healing, Tarot, Diseño Humano, Med. Ayurvédica, Masaje Ayurvédico, Fisioterapia) · **Eventos** (Workshops, Retiros, Charlas, Formaciones, Círculos) · **Alquiler de espacios** _(2ª etapa)_ · **Tienda** _(2ª etapa)_ · **Café** _(2ª etapa)_.

### 4.2 Egresos — clasificación
- [x] Categorías de egreso (a sembrar): **Honorarios** (profesores, terapeutas, facilitadores invitados, recepcionista, directoras) · **Infraestructura** (alquiler, electricidad, agua, internet, celular, limpieza, seguridad) · **Marketing** (redes, diseño, publicidad, foto/audiovisual, web) · **Administración** (papelería, software, dominio, hosting, licencias) · **Mantenimiento** (reparaciones, pintura, jardinería, equipamiento) · **Compras** (material de yoga, equipamiento, decoración) · **Impuestos** (IVA, IRP, otros) · **Gastos bancarios** (comisiones, transferencias, POS, Bancard). — _Fase 7 (sembrado por AccountingCatalogSeeder)_

### 4.3 Centros de Costo
- [x] Asignar ingreso/gasto a una unidad: Yoga, Terapias, Eventos, Tienda, Café, Administración. — _Fase 7_

### 4.4 Métodos de Pago
- [x] Efectivo, transferencia bancaria, Bancard POS, tarjeta crédito, tarjeta débito (para conciliar caja). — _Fase 7_

### 4.5 Facturación
> **Decisión white-label (SPEC.md):** modelar genérico (`tax_id`, `tax_condition`, módulo activable), no "RUC/IVA" fijos.
- [x] Por venta: ¿se emitió factura?, N° de factura, nombre/razón social, RUC (tax_id), condición IVA (tax_condition). — _Fase 7 (campos invoice_* en Transaction)_
- [ ] Exportar reporte para el contador. — _Fase 8 (reportes)_

### 4.6 Caja
- [x] Caja inicial + ingresos − egresos = caja disponible, filtrable por fecha. — _Fase 7_ · _Modelado como **cuentas/cajas** (Account): efectivo y cuentas bancarias con **saldo inicial** (caja inicial) y **saldo actual = saldo inicial + Σ ingresos − Σ egresos de SUS transactions**. Cada transacción entra/sale de una cuenta (auto-ruteada por método de pago). Resumen del mes (CashSummary, excluye transferencias) + filtro por fechas._
- [x] **Cuentas / cajas** (ej. Caja chica, Cuenta Banco 0082) con saldo, y **transferencias internas** entre cuentas. — _Fase 7_ · _Una transferencia genera **2 transactions** (egreso en origen + ingreso en destino, marcadas con source=Transfer), así el saldo de cada caja sale puro de transactions; se excluyen del resultado (P&L) vía scope `notTransfer`._

### 4.7 Dashboard Administrativo
- [x] **Resumen del día:** alumnos asistiendo, prácticas, acompañamientos, eventos, ingresos, egresos, saldo del día. — _Fase 8 (widget TodayOverview)_
- [x] **Estado del negocio (mes):** ingresos, egresos, resultado, margen %. — _Fase 8 (widget BusinessState)_
- [~] **Comunidad:** alumnos activos/nuevos, membresías activas/próximas a vencer, prácticas más concurridas, profesional con más reservas, terapia más solicitada, eventos realizados, % ocupación. — _Fase 8_ · _Hecho: activos, nuevos, membresías activas/por vencer (widget CommunityStats). Pendiente: rankings (prácticas/profesional/terapia más concurridos) y % ocupación._
- [~] **Alertas:** membresías por vencer, taller con pocos inscriptos, clases completas con lista de espera, facturas pendientes, sesiones canceladas. — _Fase 8_ · _Hecho: membresías por vencer (widget-tabla). Pendiente: resto de alertas._

### 4.8 Reportes
- [ ] Filtros: día/semana/mes/año, profesional, actividad, categoría, medio de pago. — _Fase 8_
- [ ] Reportes sugeridos: ingresos por actividad, ingresos por profesional, ocupación de clases (promedio/asistencia/cancelaciones/no-shows), membresías (vendidas/vencidas/renovadas/tasa de renovación), alumnos (nuevos/activos/inactivos/asistencia promedio), balance económico (ingresos/egresos/resultado). — _Fase 8_

### 4.9 Liquidación de Honorarios
- [x] Config por profesional con esquema propio: monto fijo por clase, % por servicio, alquiler de consultorio, etc. (ej.: Eloisa fijo grupal + 80% tarot; Neli 70% Reiki/KAP; Magu % Diseño Humano; invitado % de workshop). — _Fase 8 (FeeScheme por actividad o por defecto, en la ficha del profesional)_
- [x] Cierre mensual automático: clases dictadas, sesiones realizadas, eventos facilitados, ingresos generados, honorarios a pagar. — _Fase 8 (HonorariumService + página "Liquidación de honorarios" con selector de mes)_

---

## 5. Futuro (pág. 22) — fuera del MVP
- [ ] Tienda online · Café · Venta de retiros · Gift Cards · Cursos online · Biblioteca · Newsletter · Programa de referidos · App móvil. Todo sobre la misma base, sin recrear usuarios. — _Futuro_

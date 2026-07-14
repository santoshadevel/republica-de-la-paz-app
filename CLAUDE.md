# CLAUDE.md — Santosha · República de la Paz (ERP)

Guía para trabajar en este repositorio. Léela antes de generar código.

## Regla #0 — Comunidad primero (INNEGOCIABLE)

**Antes de construir cualquier módulo o feature, investigá qué ofrece la comunidad de
Laravel/Filament.** El orden de preferencia es:

1. **Una feature nativa de Laravel/Filament** que ya lo resuelva.
2. **Un paquete de la comunidad** maduro y mantenido (ej. spatie/*, plugins de Filament).
3. **Último recurso: escribirlo desde cero** — solo si 1 y 2 no aplican, y dejando
   registrado por qué.

Esto aplica a CADA tarea. Al abrir un módulo, el primer paso es la búsqueda/evaluación
de paquetes; recién después se decide la implementación. Documentar la decisión (qué se
evaluó y qué se eligió) en el commit o en este archivo cuando sea relevante.
_Nota: usar features del framework (casts, JSON, policies, colas, etc.) NO cuenta como
"desde cero" — es la opción 1._

### Criterios OBLIGATORIOS para usar un paquete (INNEGOCIABLE)

- **Confirmación del usuario SIEMPRE.** Antes de instalar/adoptar cualquier paquete, el
  usuario lo tiene que aprobar. Presentar la evaluación (qué resuelve, stars, última
  actualización, encaje) y esperar el OK. Nunca agregar una dependencia sin confirmación.
- **NUNCA** usar un paquete que:
  - no esté respaldado/sostenido por la comunidad, o
  - tenga **menos de 1.000 stars** en GitHub, o
  - no haya sido **actualizado en los últimos 3 meses**.
- Los paquetes oficiales de Laravel/Filament y de Spatie cumplen estos criterios por
  defecto, pero igual requieren confirmación del usuario antes de sumarse.

## Qué es

ERP liviano modular para **Santosha · República de la Paz**, un centro de yoga y
bienestar en Paraguay. Una única base de datos alimenta todos los módulos, de modo
que un solo evento de negocio (p. ej. la venta de una membresía) impacta a la vez en
CRM, habilita reservas, registra el ingreso contable y deja al alumno disponible para
comunicación.

El plan completo, alcance por fases y criterios de aceptación están en [SPEC.md](SPEC.md).
Los **requisitos del cliente transcritos del PDF** (fuente de verdad del negocio, checklist
trazable por fase) están en [docs/REQUISITOS.md](docs/REQUISITOS.md) — **nada del PDF debe
perderse**: al construir cada fase, verificar y marcar sus ítems ahí.

- **Marca inicial:** Santosha (Paraguay). Moneda por defecto: Guaraníes (Gs) — pero
  **configurable**, ver "Arquitectura white-label".
- **Identificación única de alumno:** por **`email`** (unique en DB). El número de
  identidad (`identity_number`, genérico) es secundario y opcional.
- **Referencias de diseño:** asanagroove.com (landing), theyogaclubbarcelona.com (calendario semanal).
- **Maquetas de la landing:** [docs/santosha-demo-html/](docs/santosha-demo-html/) — HTML
  estático hecho con Claude Design. Es la **referencia visual** de la Fase 9 (identidad,
  paleta, secciones, copy), no código a copiar tal cual: se traduce a Blade + Tailwind.
  Ver su [CLAUDE.md](docs/santosha-demo-html/CLAUDE.md) para paleta, tipografía y voz.

## Arquitectura white-label / API (IMPORTANTE)

La plataforma puede venderse como **white-label** a otras marcas y a futuro un **bot AI**
cubrirá ~99% de un rol de agendamiento/coordinación. Reglas transversales:

- **Nada específico de Santosha/Paraguay hardcodeado.** Nombres de dominio genéricos:
  `identity_number` (no `cedula`), etc.
- **Moneda y precios configurables.** No asumir Guaraníes. Dinero = **enteros en unidad
  mínima** + `currency_code`, con decimales/locale por **configuración** (Guaraní usa 0
  decimales; otras monedas, 2). Nunca una constante de moneda en el código de dominio.
- **Identidad:** ficha única por `email`. `identity_number` es **nullable** y su
  **unicidad se valida en código** (validation rule), no como índice duro — en otras
  marcas puede no existir.
- **Multi-tenancy:** por ahora **genérico, una marca por deploy** (sin `tenant_id`).
  Diseñar con nombres/estructura que no impidan migrar a multi-tenant real más adelante.
- **Lógica de negocio en `app/Actions` o `app/Services`,** reutilizable por Filament
  **y** por la futura API. Los Resources de Filament y (luego) los controllers de API
  solo orquestan; no contienen reglas de negocio.
- **API + MCP:** la API REST (Sanctum) y el servidor MCP encima se construyen **más
  adelante** (cuando exista el dominio de agenda, ~tras Fase 5/6). Hasta entonces, la
  disciplina de services/actions deja todo listo para exponerlo sin refactor.
- **Bot AI (qué hace):** especificado en [docs/BOT.md](docs/BOT.md) — capacidades,
  mapeo bot→dominio (Actions/Services), superficie API/MCP y gaps. Fuente de verdad del
  agente, análoga a REQUISITOS.md para el negocio.

## Convención de idioma (IMPORTANTE)

- **Todo el código va en inglés:** nombres de tablas y columnas de base de datos,
  clases, métodos, variables, migraciones, enums, factories, seeders, rutas internas,
  tests. Sin excepciones.
- **El dominio y la UI van en español:** labels de Filament, textos de cara al usuario,
  la landing pública, mensajes. El locale de la app es `es`.
- Traducir siempre el concepto de negocio a un identificador en inglés. Ejemplos de
  mapeo en las secciones de Roles y Módulos más abajo.

## Stack

| Capa            | Tecnología                                   |
|-----------------|----------------------------------------------|
| Backend / Admin | Laravel 13 + FilamentPHP 5 (panel `/admin`)  |
| Base de datos   | MySQL 8.4                                     |
| Runtime         | PHP 8.4 (php-fpm) + Nginx                     |
| Infraestructura | Docker / Docker Compose                      |
| Landing pública | Blade + Tailwind + Alpine, separado del panel — maquetas en [docs/santosha-demo-html/](docs/santosha-demo-html/) |

## Entorno de desarrollo

No hay PHP ni Composer en el host: **todo artisan/composer se corre dentro de Docker.**

```bash
cp .env.example .env
docker compose up -d --build       # app (php-fpm) + web (nginx) + db (mysql)

docker compose exec app php artisan <cmd>     # artisan
docker compose exec app composer <cmd>        # composer
docker compose exec app php artisan test      # tests
```

- App: http://localhost:8000 · Panel: http://localhost:8000/admin
- Admin de prueba: `admin@santosha.test` / `password`
- Puertos host: web `8000` (`APP_PORT`), MySQL `3310` (`DB_PORT_HOST`). El puerto de
  MySQL no es 3306 porque ese puerto ya está ocupado por otros proyectos locales.

El entrypoint (`docker/php/entrypoint.sh`) instala dependencias, genera `APP_KEY`,
espera a MySQL y corre migraciones automáticamente al levantar el contenedor `app`.

## Convenciones de código

### Regla #1 — Escrituras SIEMPRE en el modelo (INNEGOCIABLE)

**Todos los queries de escritura (`create`, `update`, `delete`, `save`, `insert`,
upserts, incrementos/decrementos, sync de relaciones) tienen que vivir 100% en el
modelo Eloquent correspondiente** — nunca en un Resource de Filament, controller,
service, action, seeder, command, job ni en ningún otro lado. Los demás componentes
llaman a métodos del modelo (p. ej. `$student->consumeCredit(...)`, `Booking::place(...)`);
no arman ni ejecutan el query de escritura por su cuenta.

- **Nunca queries en los `.blade`.** Las vistas no leen ni escriben datos: reciben todo
  ya resuelto desde el modelo/componente. Ni siquiera lecturas (`Model::where(...)`) van
  en un Blade.
- Los **services/actions** siguen siendo el lugar de la lógica de negocio con tiempo
  (saldo, cancelaciones, cupos, concurrencia), pero **orquestan**: la mutación final la
  hace un método del modelo. El service decide *cuándo/por qué*; el modelo ejecuta *el
  cómo* de la escritura.
- Esto mantiene la lógica de persistencia en un solo lugar, testeable y reutilizable por
  Filament y por la futura API/MCP sin duplicar reglas.

### Regla #2 — A `main` solo se entra por Pull Request (INNEGOCIABLE)

**Nunca commitear ni mergear directo a `main`.** Todo cambio, sin importar el tamaño,
viaja en una rama y se integra **vía Pull Request**.

- Ni un hotfix, ni un typo, ni un cambio "de una línea" van directo a `main`.
- Nada de `git merge` local hacia `main` ni `git push origin main`. El merge lo hace el
  PR una vez aprobado.
- Si por error se empezó a trabajar sobre `main`: crear la rama **antes** de commitear
  (`git switch -c feat/...`) y abrir el PR desde ahí.
- El PR es el punto donde corre la revisión (`/revision`) y quedan registrados el qué y
  el porqué del cambio.

- Seguir las **convenciones oficiales de Laravel** y formatear con **Pint**
  (`docker compose exec app ./vendor/bin/pint`).
- Modelos en singular (`Student`, `Membership`), tablas en plural snake_case
  (`students`, `memberships`), pivotes en orden alfabético (`practitioner_room`).
- Permisos por **roles con `spatie/laravel-permission`** (estándar de la comunidad,
  integra con los Gates de Laravel: `$user->can()`, `@can`, Policies siguen funcionando).
  Un usuario puede tener **varios roles** (relación muchos-a-muchos). El enum
  `App\Enums\Role` es la única fuente de verdad de las claves de rol. Una Policy por módulo.
- Reglas de negocio con lógica temporal (saldo, cancelaciones, cupos) van en
  services/actions dedicados, no en los controllers ni en los recursos de Filament.
  Cuidar concurrencia en el descuento de cupos.
- Migraciones y seeders deben correr limpios (`migrate:fresh --seed`).

## Roles

**Cuatro roles** (el rol `director` se pospone). Un usuario puede tener **varios roles**.
En la UI se muestran en español (ver `App\Enums\Role::label()`); en código se usa el
identificador en inglés. Solo los roles de **staff** (`practitioner`, `receptionist`,
`admin`, definidos en `Role::STAFF`) acceden al panel Filament; el `student` no.

| Rol (UI)            | Identificador | Descripción |
|---------------------|---------------|-------------|
| Alumno              | `student`     | Miembro de la República. Tiene ficha única identificada por cédula. Compra membresías/pases, reserva prácticas grupales, agenda acompañamientos individuales y se inscribe a eventos. Consume y ve su propio saldo de prácticas; acceso limitado a sus propios datos. **No entra al panel admin.** |
| Profesional         | `practitioner`| Profesor/a o terapeuta que imparte prácticas grupales, acompañamientos individuales y eventos. Ve su agenda y las reservas de sus clases; registra asistencia. Base para la liquidación de honorarios (esquema fijo o % por servicio). No accede a contabilidad global. |
| Recepción           | `receptionist`| Personal de mostrador para la operación diaria. Gestiona alumnos (alta/edición de fichas), vende membresías y pases, cobra y registra pagos, hace reservas y cancelaciones en nombre del alumno, y consulta el dashboard operativo del día. No accede a reportes financieros ni configuración. |
| Administración      | `admin`       | Gestión completa del sistema: configura salas, actividades, profesionales, membresías/pases, métodos de pago, categorías contables y centros de costo. Accede a CRM, agendamientos, contabilidad completa (ingresos/egresos, caja, facturación), reportes y liquidación de honorarios. |

> `director` pospuesto: si se retoma, sería dirección con visión total del negocio
> (dashboards estratégicos, reportes, liquidaciones), potencialmente equiparable a `admin`.
>
> Los roles se siembran con `RoleSeeder`. Los permisos finos por módulo (qué puede
> ver/editar cada rol) se implementan con Policies/permisos de spatie a medida que se
> construye cada módulo.

## Módulos (y su nombre en código)

| Módulo (negocio)              | Concepto en código (orientativo)                              |
|-------------------------------|---------------------------------------------------------------|
| Salas                         | `Room`                                                        |
| Actividades / prácticas       | `Activity` / `Practice` / `Session`                          |
| Profesionales / terapeutas    | `Practitioner`                                               |
| Clientes / alumnos            | `Student` (ficha única por email; `identity_number` opcional) |
| Membresías y pases            | `MembershipPlan` (catálogo con JSON `rules`); venta/saldo/vigencia → Fase 4 |
| Agendamientos grupales        | `GroupClass` / `Booking` (cupos, saldo, política de cancelación) |
| Acompañamientos individuales  | `Appointment`                                               |
| Eventos                       | `Event` (workshops, charlas, retiros, círculos, formaciones) |
| Contabilidad                  | `Transaction`, `PaymentMethod`, `Category`, `CostCenter`     |
| Reportes / dashboards         | Widgets de Filament + reportes                               |

## Decisiones de paquetes (comunidad primero)

Registro de evaluaciones (Regla #0):

- **Roles/permisos:** `spatie/laravel-permission` (adoptado, Fase 1).
- **Membresías/suscripciones (Fase 4):** **modelo propio liviano** sobre el
  `MembershipPlan` existente — `StudentMembership` + ledger `CreditMovement`. Diseño
  detallado en [docs/MODULO_MEMBRESIAS.md](docs/MODULO_MEMBRESIAS.md). Evaluados y
  **descartados**:
  - `laravelcm/laravel-subscriptions`: compatible con L13 pero duplica `MembershipPlan`
    y su uso por período no encaja con el pool de créditos que vence.
  - `laravel/cashier`: facturación vía Stripe; los pagos en PY son manuales/Bancard y el
    pase es prepago con saldo, no una suscripción de tarjeta (anti white-label).
- Plugins Filament de suscripción (tomatophp, SubKit) descartados: orientados a
  Stripe/Cashier, no a pago manual + saldo de prácticas.
- **Dinero:** implementación propia liviana (`App\Support\Money` + cast) sobre features
  nativas; suficiente para el caso y evita acoplar una lib de money pesada.
- **Disponibilidad del profesional:** `spatie/opening-hours` (adoptado, aprobado por el
  usuario). 1.7k★, v4.2.2 (jul-2026), PHP ^8.2. Es librería de *lógica* (value object): la
  persistencia queda en tablas propias (`practitioner_availabilities` + `_exceptions`) y el
  modelo `Practitioner` construye el `OpeningHours` al vuelo (`openingHours()`, `isAvailableAt()`).
- **Calendario (Agenda):** FullCalendar JS vendorizado en `public/js/vendor/fullcalendar/`
  (20.6k★, MIT) sobre página Filament propia; los wrappers PHP (saade 405★, guava 306★) se
  descartaron por <1000★.

## Reglas de negocio clave (referencia rápida)

- **Prácticas grupales:** reservar descuenta 1 del saldo; cancelar **> 1 h antes**
  reintegra el saldo; después se consume.
- **Acompañamientos individuales:** cancelar con **< 24 h** cobra el 50 %.
- **Contabilidad:** cada movimiento se registra con categoría, subcategoría, unidad de
  negocio (centro de costo) y método de pago.
- **Liquidación de honorarios:** por profesional, esquema fijo o % por servicio.

## Estado / fases

Fase 0 (Infra) ✅ completa. Siguiente: **Fase 1 — Auth y roles (Gates/Policies)**.
Ver la lista de tareas por fase en [SPEC.md](SPEC.md).

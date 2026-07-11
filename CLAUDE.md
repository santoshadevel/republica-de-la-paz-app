# CLAUDE.md — Santosha · República de la Paz (ERP)

Guía para trabajar en este repositorio. Léela antes de generar código.

## Qué es

ERP liviano modular para **Santosha · República de la Paz**, un centro de yoga y
bienestar en Paraguay. Una única base de datos alimenta todos los módulos, de modo
que un solo evento de negocio (p. ej. la venta de una membresía) impacta a la vez en
CRM, habilita reservas, registra el ingreso contable y deja al alumno disponible para
comunicación.

El plan completo, alcance por fases y criterios de aceptación están en [SPEC.md](SPEC.md).

- **Moneda:** Guaraníes (Gs).
- **Identificación única de alumno:** número de cédula.
- **Referencias de diseño:** asanagroove.com (landing), theyogaclubbarcelona.com (calendario semanal).

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
| Landing pública | Frontend separado del panel (Claude Design)  |

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

- Seguir las **convenciones oficiales de Laravel** y formatear con **Pint**
  (`docker compose exec app ./vendor/bin/pint`).
- Modelos en singular (`Student`, `Membership`), tablas en plural snake_case
  (`students`, `memberships`), pivotes en orden alfabético (`practitioner_room`).
- Permisos por **roles usando Gates/Policies de Laravel** (no un paquete externo salvo
  que se decida lo contrario). Una Policy por módulo.
- Reglas de negocio con lógica temporal (saldo, cancelaciones, cupos) van en
  services/actions dedicados, no en los controllers ni en los recursos de Filament.
  Cuidar concurrencia en el descuento de cupos.
- Migraciones y seeders deben correr limpios (`migrate:fresh --seed`).

## Roles

Cinco roles. En la UI se muestran en español; en código se usa el identificador en inglés.

| Rol (UI)            | Identificador | Descripción |
|---------------------|---------------|-------------|
| Alumno              | `student`     | Miembro de la República. Tiene ficha única identificada por cédula. Compra membresías/pases, reserva prácticas grupales, agenda acompañamientos individuales y se inscribe a eventos. Consume y ve su propio saldo de prácticas; acceso limitado a sus propios datos. |
| Profesional         | `practitioner`| Profesor/a o terapeuta que imparte prácticas grupales, acompañamientos individuales y eventos. Ve su agenda y las reservas de sus clases; registra asistencia. Base para la liquidación de honorarios (esquema fijo o % por servicio). No accede a contabilidad global. |
| Recepción           | `receptionist`| Personal de mostrador para la operación diaria. Gestiona alumnos (alta/edición de fichas), vende membresías y pases, cobra y registra pagos, hace reservas y cancelaciones en nombre del alumno, y consulta el dashboard operativo del día. No accede a reportes financieros ni configuración. |
| Administración      | `admin`       | Gestión completa del sistema: configura salas, actividades, profesionales, membresías/pases, métodos de pago, categorías contables y centros de costo. Accede a CRM, agendamientos, contabilidad completa (ingresos/egresos, caja, facturación), reportes y liquidación de honorarios. |
| Directora           | `director`    | Dirección del centro. Visión total del negocio: dashboards estratégicos (estado del negocio/mes), reportes financieros y de gestión, liquidaciones. Acceso de solo lectura amplio más las decisiones de alto nivel; puede tener las mismas capacidades que `admin` según se defina. |

> Los permisos concretos por módulo se implementan con Gates/Policies en la **Fase 1**.
> El detalle fino (qué puede ver/editar cada rol en cada módulo) se refinará ahí.

## Módulos (y su nombre en código)

| Módulo (negocio)              | Concepto en código (orientativo)                              |
|-------------------------------|---------------------------------------------------------------|
| Salas                         | `Room`                                                        |
| Actividades / prácticas       | `Activity` / `Practice` / `Session`                          |
| Profesionales / terapeutas    | `Practitioner`                                               |
| Clientes / alumnos            | `Student` (ficha única por cédula)                           |
| Membresías y pases            | `Membership`, `Pass` (Prueba gratuita, Ciudadano 4, Comunidad 12, República ilimitada) |
| Agendamientos grupales        | `GroupClass` / `Booking` (cupos, saldo, política de cancelación) |
| Acompañamientos individuales  | `Appointment`                                               |
| Eventos                       | `Event` (workshops, charlas, retiros, círculos, formaciones) |
| Contabilidad                  | `Transaction`, `PaymentMethod`, `Category`, `CostCenter`     |
| Reportes / dashboards         | Widgets de Filament + reportes                               |

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

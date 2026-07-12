# Santosha · República de la Paz — ERP

ERP liviano modular (Laravel 13 + FilamentPHP 5 + MySQL) sobre Docker.

- [SPEC.md](SPEC.md) — objetivo, alcance, decisiones y plan de fases.
- [docs/REQUISITOS.md](docs/REQUISITOS.md) — requisitos del cliente transcritos del PDF (fuente de verdad, checklist por fase).
- [CLAUDE.md](CLAUDE.md) — convenciones para trabajar en el repo.

## Stack

- **Backend / Admin:** Laravel 13 + FilamentPHP 5 (panel `/admin`)
- **Base de datos:** MySQL 8.4
- **Runtime:** PHP 8.4 (php-fpm) + Nginx, todo en Docker

## Puesta en marcha

Requisitos: Docker + Docker Compose. No hace falta PHP/Composer en el host.

```bash
cp .env.example .env          # ya viene configurado para Docker
docker compose up -d --build  # levanta app (php-fpm), web (nginx) y db (mysql)
```

El contenedor `app` instala dependencias, genera `APP_KEY`, espera a MySQL y
corre las migraciones automáticamente (ver `docker/php/entrypoint.sh`).

- App: http://localhost:8000
- Panel admin: http://localhost:8000/admin

### Usuario admin de ejemplo

```
email:    admin@santosha.test
password: password
```

Para crear otro usuario del panel:

```bash
docker compose exec app php artisan make:filament-user
```

## Comandos útiles

```bash
docker compose exec app php artisan migrate        # migraciones
docker compose exec app php artisan tinker         # REPL
docker compose exec app composer install           # dependencias
docker compose logs -f app                         # logs
docker compose down                                # detener (conserva la DB)
docker compose down -v                             # detener y borrar la DB
```

## Puertos

| Servicio | Host | Contenedor | Variable        |
|----------|------|------------|-----------------|
| Nginx    | 8000 | 80         | `APP_PORT`      |
| MySQL    | 3310 | 3306       | `DB_PORT_HOST`  |

Ajustables en `.env` si hay conflictos con otros servicios locales.

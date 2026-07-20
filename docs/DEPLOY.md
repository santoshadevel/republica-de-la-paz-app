# Deploy — Santosha ERP (entorno DEV en DigitalOcean)

Guía paso a paso (**runbook**) para dejar andando el entorno **dev** desplegado y
que a partir de ahí cada **push a la rama `dev`** haga deploy automático.

## Arquitectura

```
  push a `dev`
       │
       ▼
  GitHub Actions ── buildea la imagen (Composer --no-dev + assets Vite) ──► GHCR
       │                                                                     │
       └── SSH al droplet ──► docker compose pull + up ──────────────────────┘
                                     │
                       ┌─────────────┴──────────────┐
                       ▼                             ▼
                  app (php-fpm)               caddy (TLS + estáticos)
                       │                             ▲
                       ▼                             │  https://santosha.dev.umbralclub.com
              MySQL administrado (DO)  ◄─── SSL ──────┘
```

- **La imagen se buildea en GitHub, no en el droplet.** El droplet de 1 GB solo baja
  y levanta la imagen: no corre `composer install` ni `vite build`.
- **MySQL** = cluster administrado de DO (no corre en el droplet).
- **TLS** lo emite Caddy solo (Let's Encrypt), siempre que el DNS ya apunte al droplet.

Archivos que participan (todos en el repo):
`docker/php/Dockerfile.prod`, `docker/php/entrypoint.prod.sh`, `docker/caddy/Caddyfile`,
`docker-compose.prod.yml`, `.env.prod.example`, `.github/workflows/deploy.yml`.

---

## 0. Prerrequisitos (una vez)

### 0.1 DNS

Creá un registro **A**:

```
santosha.dev.umbralclub.com  →  206.189.184.221
```

Verificá que resuelva antes de seguir (el TLS falla si el DNS no apunta al droplet):

```bash
dig +short santosha.dev.umbralclub.com   # debe devolver 206.189.184.221
```

### 0.2 Datos del cluster MySQL administrado

En el panel de DO → tu cluster → **Connection Details**, anotá: host, puerto (≈ `25060`),
usuario (`doadmin`), password, nombre de la base (`defaultdb`) y **descargá el
certificado CA** (`ca-certificate.crt`).

> Opcional recomendado: en el panel del cluster, en **Users & Databases**, creá una base
> `santosha` dedicada en vez de usar `defaultdb`. Si lo hacés, ajustá `DB_DATABASE`.

### 0.3 Firewall del cluster

En **Settings → Trusted Sources** del cluster, agregá el droplet (por nombre o por su IP
`206.189.184.221`) para que pueda conectarse.

---

## 1. Bootstrap del droplet (una vez)

Conectate como root:

```bash
ssh root@206.189.184.221
```

### 1.1 Swap (clave con 1 GB de RAM)

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
sysctl -w vm.swappiness=10
echo 'vm.swappiness=10' >> /etc/sysctl.conf
```

### 1.2 Docker + Compose

```bash
curl -fsSL https://get.docker.com | sh
docker --version && docker compose version
```

### 1.3 Usuario de deploy

Usuario dedicado, sin sudo, con acceso a Docker (lo usa GitHub Actions por SSH):

```bash
adduser --disabled-password --gecos "" deploy
usermod -aG docker deploy
mkdir -p /home/deploy/.ssh && chmod 700 /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys && chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```

### 1.4 Firewall del droplet

```bash
ufw allow OpenSSH
ufw allow 80
ufw allow 443
ufw --force enable
```

---

## 2. Clave SSH de deploy (una vez)

En **tu máquina local**, generá un par dedicado (sin passphrase, lo usa el workflow):

```bash
ssh-keygen -t ed25519 -C "gha-deploy-santosha" -f ~/.ssh/santosha_deploy -N ""
```

Autorizá la **pública** en el droplet:

```bash
ssh-copy-id -i ~/.ssh/santosha_deploy.pub deploy@206.189.184.221
# o, manualmente, pegá el contenido de ~/.ssh/santosha_deploy.pub en
# /home/deploy/.ssh/authorized_keys del droplet.
```

Probá que entra:

```bash
ssh -i ~/.ssh/santosha_deploy deploy@206.189.184.221 "docker ps"
```

---

## 3. Secrets en GitHub (una vez)

En el repo `santoshadevel/republica-de-la-paz-app` → **Settings → Secrets and variables →
Actions**, o por CLI:

```bash
gh secret set DEPLOY_HOST    --body "206.189.184.221"
gh secret set DEPLOY_USER    --body "deploy"
gh secret set DEPLOY_SSH_KEY < ~/.ssh/santosha_deploy      # la clave PRIVADA
```

> No hace falta secret para GHCR: el build usa el `GITHUB_TOKEN` propio del workflow, y
> el droplet se loguea a GHCR una sola vez en el paso 4.4.

---

## 4. Configuración en el droplet (una vez)

Ya como usuario `deploy` (`ssh -i ~/.ssh/santosha_deploy deploy@206.189.184.221`):

### 4.1 Carpeta del proyecto

```bash
sudo mkdir -p /opt/santosha && sudo chown deploy:deploy /opt/santosha
cd /opt/santosha
```

### 4.2 Copiar los archivos de deploy

Desde **tu máquina local**, parado en el repo:

```bash
scp -i ~/.ssh/santosha_deploy \
  docker-compose.prod.yml docker/caddy/Caddyfile \
  deploy@206.189.184.221:/opt/santosha/
```

### 4.3 CA del MySQL + `.env`

En el droplet, `/opt/santosha/`:

- Subí el `ca-certificate.crt` del cluster (paso 0.2) a `/opt/santosha/ca-certificate.crt`.
- Creá el `.env` a partir de la plantilla y completá los valores reales:

```bash
# copiá el contenido de .env.prod.example del repo y pegalo en:
nano /opt/santosha/.env
```

Completá `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `ACME_EMAIL`.
Dejá `APP_KEY` vacío por ahora.

### 4.4 Login a GHCR (para poder bajar la imagen privada)

Generá en GitHub un **Personal Access Token (classic)** con scope `read:packages` y:

```bash
echo "TU_PAT" | docker login ghcr.io -u TU_USUARIO_GITHUB --password-stdin
```

### 4.5 Generar el APP_KEY

```bash
docker pull ghcr.io/santoshadevel/republica-de-la-paz-app:dev
docker run --rm ghcr.io/santoshadevel/republica-de-la-paz-app:dev \
  php artisan key:generate --show
```

Pegá el `base64:...` que imprime en `APP_KEY=` del `.env`.

> La imagen `:dev` existe recién después del primer build (paso 5). Si todavía no
> corriste el workflow, hacé el paso 5 primero y volvé a generar el APP_KEY acá.

---

## 5. Primer deploy

Creá la rama `dev` y pusheala: eso dispara el workflow.

```bash
# desde el repo, con main actualizado:
git switch -c dev origin/main
git push -u origin dev
```

Mirá el progreso en la pestaña **Actions** del repo (o `gh run watch`). El workflow:

1. buildea la imagen y la sube a GHCR como `:dev`,
2. entra por SSH al droplet y hace `docker compose pull && up -d`.

El contenedor `app`, al arrancar, espera la DB, **migra**, cachea config/rutas/vistas y
publica los assets. Caddy pide el certificado TLS.

Verificá:

```bash
curl -I https://santosha.dev.umbralclub.com
# en el droplet:
cd /opt/santosha && docker compose -f docker-compose.prod.yml logs -f app
```

Admin del panel: `https://santosha.dev.umbralclub.com/admin`.

---

## 6. Día a día

- **Deploy** = merge/push a `dev`. No se toca el droplet a mano.
- `main` sigue siendo integración (solo se entra por PR — Regla #2). Para liberar a dev:
  abrí PR a `main`, mergealo, y actualizá `dev`:
  ```bash
  git switch dev && git merge --ff-only origin/main && git push
  ```
  (o mergeá directamente la rama de feature a `dev` para probar antes de main).

### Prod más adelante

Cuando dev esté sólido, se agrega producción con mínimo esfuerzo:
1. Otro droplet + otra base (recomendado) o el mismo con otro dominio.
2. En `.github/workflows/deploy.yml`: sumar `prod` a `branches` y un job `deploy-prod`
   con secrets `PROD_HOST` / `PROD_USER` / `PROD_SSH_KEY`, tag de imagen `:prod`.
3. Repetir pasos 1–4 en el nuevo server con su `.env`.

---

## 7. Troubleshooting

| Síntoma | Qué mirar |
|---|---|
| TLS no emite (`SSL error`) | El DNS todavía no apunta al droplet, o los puertos 80/443 cerrados. `dig` + `ufw status`. |
| `SQLSTATE ... SSL` / no conecta a la DB | Falta el CA (`ca-certificate.crt`) o el droplet no está en **Trusted Sources** del cluster. |
| El deploy no baja la imagen | El droplet no está logueado a GHCR (paso 4.4) o el PAT venció. |
| 502 / 404 en estáticos | Revisá `docker compose logs app`: el `rsync` a `/webroot` corre al final del arranque. |
| Se queda sin memoria al buildear | No debería: el build es en GitHub. Si falla en el droplet, confirmá que el swap está activo (`swapon --show`). |
| Ver errores de la app | Temporalmente `APP_DEBUG=true` en el `.env` y `docker compose up -d` de nuevo. Volvé a `false` después. |

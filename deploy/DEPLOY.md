# Publicar ProLech con HTTPS

Hay dos caminos; elige uno:

- **Opción A — Docker** (recomendada): todo en contenedores, Caddy saca el certificado solo.
- **Opción B — Nginx sin Docker**: script `deploy/instalar-vps.sh` para Ubuntu 24.04 (Nginx + PHP-FPM 8.5 + MySQL + certbot).

## Opción A — Docker

Este kit levanta todo el backend en un servidor Linux (VPS) con **HTTPS automático**:

| Servicio | Qué hace |
|---|---|
| `web` (Caddy) | Pide y renueva el certificado de Let's Encrypt; sirve CSS/JS/imágenes y pasa lo demás a Laravel |
| `app` (PHP-FPM 8.5) | Laravel: panel web, tienda y API de la app; corre las migraciones al iniciar |
| `db` (MySQL 8.4) | Base de datos con volumen persistente |

## Lo que necesitas (cuentas tuyas)
1. Un **VPS** con Docker (DigitalOcean, Contabo, Hetzner, AWS Lightsail…; 2 GB de RAM bastan).
2. Un **dominio** (por ejemplo `api.prolech.pe`) con un registro **A** que apunte a la IP del VPS.
3. Los puertos **80 y 443** abiertos.

## Pasos
```bash
git clone <tu-repo> prolech && cd prolech
cp deploy/.env.production.example .env
nano .env          # DOMINIO, APP_URL, DB_PASSWORD (y Nubefact/Ultramsg si los tienes)
cd deploy
docker compose --env-file ../.env run --rm app php artisan key:generate --show  # copia la clave a APP_KEY en ../.env
docker compose --env-file ../.env up -d --build
docker compose exec app php artisan db:seed --force             # solo la primera vez (datos base)
```
Abre `https://TU-DOMINIO/login`. El candado aparece solo; Caddy renueva el certificado cada 60 días.

## Actualizar
```bash
git pull && cd deploy && docker compose --env-file ../.env up -d --build
```

## Copias de seguridad
Se hacen **solas todos los días a las 02:00** (hora de Lima) en `storage/app/respaldos/prolech-AAAA-MM-DD_HHMMSS.sql.gz`.
Se guardan los últimos 14 (`RESPALDO_CONSERVAR`). Si una falla, los administradores reciben un aviso en el panel.

```bash
docker compose exec app php artisan prolech:respaldo          # respaldo ahora mismo
docker compose exec app ls -lh storage/app/respaldos          # ver los respaldos
docker compose cp app:/var/www/html/storage/app/respaldos ./respaldos   # bajarlos al VPS
```
Los respaldos viven en el mismo servidor: **copia uno a otro lugar cada semana** (tu PC, Google Drive),
porque si se pierde el VPS se pierden con él.

Restaurar uno (reemplaza los datos actuales):
```bash
gunzip -c respaldos/prolech-2026-10-26_020000.sql.gz | docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

## Tareas programadas
`php artisan schedule:list` muestra todas. En Docker las arranca el contenedor `app`; sin Docker, el cron de `instalar-vps.sh`.

| Hora | Tarea |
|---|---|
| 02:00 | Respaldo de la base de datos |
| 03:00 | Borra códigos 2FA vencidos y notificaciones leídas de más de 90 días |
| 03:10 | Borra tokens de la API vencidos |
| 03:20 | Borra trabajos fallidos de la cola de más de 30 días |
| 06:30 | Aviso al admin y al jefe de producción de insumos en stock mínimo |

## Opción B — Nginx sin Docker
```bash
ssh root@IP-DEL-VPS
git clone <tu-repo> /root/prolech-src && cd /root/prolech-src/deploy
DOMINIO=api.tu-dominio.pe CORREO=tu@correo.pe REPO=<tu-repo> bash instalar-vps.sh
```
El script instala los paquetes, crea la base de datos con una clave aleatoria, compila los assets,
saca el certificado con certbot (se renueva solo), configura Nginx (`deploy/nginx/prolech.conf`)
y deja la cola como servicio `prolech-cola`.

Actualizar: `cd /var/www/prolech && git pull && composer install --no-dev -o && npm run build && php artisan migrate --force && php artisan optimize`

## App Android
En la pantalla de login de la app, toca **Servidor** y escribe `https://TU-DOMINIO`.
El APK **release** solo acepta HTTPS (el debug permite HTTP para probar en la red local).

## Lista de control de seguridad
- `APP_DEBUG=false` y `APP_ENV=production` (oculta errores y las credenciales demo del login).
- Cambia las contraseñas de los usuarios sembrados antes de usarlo con datos reales.
- El login bloquea 5 intentos fallidos por minuto por correo.

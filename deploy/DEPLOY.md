# Publicar ProLech con HTTPS

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
```bash
docker compose exec db sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > respaldo-$(date +%F).sql
```

## App Android
En la pantalla de login de la app, toca **Servidor** y escribe `https://TU-DOMINIO`.
El APK **release** solo acepta HTTPS (el debug permite HTTP para probar en la red local).

## Lista de control de seguridad
- `APP_DEBUG=false` y `APP_ENV=production` (oculta errores y las credenciales demo del login).
- Cambia las contraseñas de los usuarios sembrados antes de usarlo con datos reales.
- El login bloquea 5 intentos fallidos por minuto por correo.

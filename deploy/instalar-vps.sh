#!/usr/bin/env bash
# Instala ProLech en un VPS Ubuntu 24.04 con Nginx + PHP-FPM 8.5 + MySQL + HTTPS (certbot).
# Uso (como root):  DOMINIO=api.tu-dominio.pe CORREO=tu@correo.pe REPO=https://github.com/tu/prolech-backend.git bash instalar-vps.sh
set -euo pipefail
: "${DOMINIO:?Define DOMINIO}"; : "${CORREO:?Define CORREO para los avisos del certificado}"; : "${REPO:?Define REPO}"
APP=/var/www/prolech
DB_CLAVE=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 28)

echo "▶ Paquetes"
apt-get update
apt-get install -y software-properties-common curl git unzip nginx mysql-server certbot python3-certbot-nginx
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php8.5-fpm php8.5-cli php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl php8.5-gd php8.5-intl php8.5-zip php8.5-bcmath
curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs

echo "▶ Base de datos"
mysql -e "CREATE DATABASE IF NOT EXISTS prolech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
          CREATE USER IF NOT EXISTS 'prolech'@'localhost' IDENTIFIED BY '${DB_CLAVE}';
          GRANT ALL ON prolech.* TO 'prolech'@'localhost'; FLUSH PRIVILEGES;"

echo "▶ Código"
[ -d "$APP/.git" ] || git clone "$REPO" "$APP"
cd "$APP"
cp -n deploy/.env.production.example .env
sed -i "s#^APP_URL=.*#APP_URL=https://${DOMINIO}#; s#^DOMINIO=.*#DOMINIO=${DOMINIO}#; s#^DB_HOST=.*#DB_HOST=127.0.0.1#; s#^DB_PASSWORD=.*#DB_PASSWORD=${DB_CLAVE}#" .env
composer install --no-dev --optimize-autoloader --no-interaction
npm install --no-audit --no-fund && npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

echo "▶ Nginx + HTTPS"
mkdir -p /var/www/certbot
sed "s/api.tu-dominio.pe/${DOMINIO}/g" deploy/nginx/prolech.conf > /etc/nginx/sites-available/prolech
# Primero solo HTTP para que certbot pueda validar el dominio
sed -n '1,/^}/p' /etc/nginx/sites-available/prolech > /etc/nginx/sites-available/prolech-http
ln -sf /etc/nginx/sites-available/prolech-http /etc/nginx/sites-enabled/prolech
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
certbot certonly --webroot -w /var/www/certbot -d "$DOMINIO" --email "$CORREO" --agree-tos --non-interactive
ln -sf /etc/nginx/sites-available/prolech /etc/nginx/sites-enabled/prolech
nginx -t && systemctl reload nginx

echo "▶ Cola y tareas programadas"
cat > /etc/systemd/system/prolech-cola.service <<UNIT
[Unit]
Description=ProLech - cola de trabajos
After=network.target mysql.service
[Service]
User=www-data
WorkingDirectory=${APP}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload && systemctl enable --now prolech-cola
( crontab -u www-data -l 2>/dev/null; echo "* * * * * cd ${APP} && php artisan schedule:run >> /dev/null 2>&1" ) | sort -u | crontab -u www-data -

echo "✅ Listo: https://${DOMINIO}/login"
echo "   Clave de MySQL guardada en ${APP}/.env (DB_PASSWORD)."
echo "   Primera vez con datos de demostración:  cd ${APP} && php artisan db:seed --force"

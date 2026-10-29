#!/bin/sh
# Al iniciar el contenedor: migraciones y cachés de producción.
set -e
cd /var/www/html
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Cola de trabajos en segundo plano
su -s /bin/sh www-data -c "php artisan queue:work --sleep=3 --tries=3 --max-time=3600" &
exec php-fpm

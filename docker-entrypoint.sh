#!/bin/bash
set -e

PORT="${PORT:-10000}"
echo ">>> MedCore container starting"
echo ">>> Binding Apache to 0.0.0.0:${PORT}"

APACHE_PORT_FILE=/etc/apache2/ports.conf
APACHE_SITE_FILE=/etc/apache2/sites-available/000-default.conf

if grep -q "^Listen " "$APACHE_PORT_FILE"; then
    sed -i "s/^Listen .*/Listen ${PORT}/" "$APACHE_PORT_FILE"
else
    echo "Listen ${PORT}" >> "$APACHE_PORT_FILE"
fi

if grep -q ":80" "$APACHE_SITE_FILE"; then
    sed -i "s/:80/:${PORT}/g" "$APACHE_SITE_FILE"
fi

mkdir -p /var/www/html/logs /var/www/html/database
chown -R www-data:www-data /var/www/html/logs /var/www/html/database
chmod -R 0775 /var/www/html/logs /var/www/html/database

if [ -f /var/www/html/.env ]; then
    chmod 0644 /var/www/html/.env
fi

# ⚡ Performance: clear OPcache after deploy so the new code is recompiled.
# The opcache.revalidate_freq=60 in our config means cached PHP files can
# stay stale for up to 60s after deploy — calling opcache_reset() at container
# start guarantees fresh code is loaded.
php -r 'if (function_exists("opcache_reset")) opcache_reset();' 2>/dev/null || true

echo ">>> Starting Apache ($@) on port ${PORT}"
exec "$@"

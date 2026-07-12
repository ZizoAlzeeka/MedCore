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

mkdir -p /var/www/html/logs
chown -R www-data:www-data /var/www/html/logs
chmod -R 0775 /var/www/html/logs

if [ -f /var/www/html/.env ]; then
    chmod 0644 /var/www/html/.env
fi

echo ">>> Starting Apache ($@) on port ${PORT}"
exec "$@"

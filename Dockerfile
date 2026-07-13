FROM php:8.2-apache

LABEL org.opencontainers.image.title="MedCore"
LABEL org.opencontainers.image.description="منصة كشف التحاليل المكررة — Duplicate Test Detection Platform"
LABEL org.opencontainers.image.source="https://github.com/ZizoAlzeeka/MedCore"

ENV DEBIAN_FRONTEND=noninteractive

# ===== Apache modules: rewrite + headers + deflate (gzip) + expires + cache =====
RUN a2enmod rewrite headers deflate expires cache

# ===== PHP extensions: pdo_mysql + opcache + apcu (in-memory cache) =====
# opcache is bundled with PHP; just enable it. APCu via PECL.
RUN docker-php-ext-install pdo_mysql && \
    docker-php-ext-enable pdo_mysql && \
    pecl install apcu && \
    docker-php-ext-enable apcu

# ===== OPcache tuning — dramatic speedup for PHP apps =====
# Use printf so \n becomes real newlines (echo in /bin/sh doesn't interpret \n)
RUN printf '[opcache]\n\
opcache.enable=1\n\
opcache.enable_cli=0\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.max_wasted_percentage=5\n\
opcache.use_cwd=1\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=60\n\
opcache.fast_shutdown=1\n' > /usr/local/etc/php/conf.d/opcache-recommended.ini

# ===== APCu user cache (for cross-request caching of small data) =====
RUN printf '[apcu]\n\
apc.enabled=1\n\
apc.enable_cli=0\n\
apc.shm_size=64M\n\
apc.ttl=7200\n\
apc.gc_ttl=3600\n' > /usr/local/etc/php/conf.d/apcu.ini

# ===== PHP production tuning =====
RUN printf 'memory_limit=256M\n\
max_execution_time=30\n\
upload_max_filesize=10M\n\
post_max_size=12M\n\
display_errors=Off\n\
log_errors=On\n\
session.gc_maxlifetime=7200\n\
session.cookie_lifetime=0\n\
session.use_strict_mode=0\n\
realpath_cache_size=4096K\n\
realpath_cache_ttl=600\n' > /usr/local/etc/php/conf.d/production.ini

# ===== Allow .htaccess overrides =====
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# ===== Apache security headers =====
RUN printf 'ServerTokens Prod\nServerSignature Off\nTraceEnable Off\n' > /etc/apache2/conf-available/security-hardening.conf && \
    a2enconf security-hardening

# ===== Apache MPM tuning for small instances (Prefork is default in php:apache) =====
RUN sed -i 's/^StartServers.*/StartServers             2/' /etc/apache2/mods-available/mpm_prefork.conf && \
    sed -i 's/^MinSpareServers.*/MinSpareServers          2/' /etc/apache2/mods-available/mpm_prefork.conf && \
    sed -i 's/^MaxSpareServers.*/MaxSpareServers          4/' /etc/apache2/mods-available/mpm_prefork.conf && \
    sed -i 's/^MaxRequestWorkers.*/MaxRequestWorkers     30/' /etc/apache2/mods-available/mpm_prefork.conf && \
    sed -i 's/^MaxConnectionsPerChild.*/MaxConnectionsPerChild   1000/' /etc/apache2/mods-available/mpm_prefork.conf

# ===== Apache deflate (gzip) + expires (cache static assets) =====
RUN printf '<IfModule mod_deflate.c>\n\
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml image/svg+xml\n\
    DeflateCompressionLevel 6\n\
</IfModule>\n\
\n\
<IfModule mod_expires.c>\n\
    ExpiresActive On\n\
    ExpiresByType text/css "access plus 7 days"\n\
    ExpiresByType application/javascript "access plus 7 days"\n\
    ExpiresByType image/png "access plus 30 days"\n\
    ExpiresByType image/jpeg "access plus 30 days"\n\
    ExpiresByType image/svg+xml "access plus 30 days"\n\
    ExpiresByType image/x-icon "access plus 30 days"\n\
    ExpiresByType font/woff2 "access plus 1 year"\n\
    ExpiresDefault "access plus 1 hour"\n\
</IfModule>\n' > /etc/apache2/conf-available/performance.conf && \
    a2enconf performance

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 0775 /var/www/html/logs && \
    chmod -R 0775 /var/www/html/database

ENV PORT=10000

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

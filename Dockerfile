FROM php:8.2-apache

LABEL org.opencontainers.image.title="MedCore"
LABEL org.opencontainers.image.description="منصة كشف التحاليل المكررة — Duplicate Test Detection Platform"
LABEL org.opencontainers.image.source="https://github.com/ZizoAlzeeka/MedCore"

ENV DEBIAN_FRONTEND=noninteractive

RUN a2enmod rewrite headers deflate

RUN docker-php-ext-install pdo_mysql && \
    docker-php-ext-enable pdo_mysql

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

RUN echo "ServerTokens Prod\nServerSignature Off\nTraceEnable Off" > /etc/apache2/conf-available/security-hardening.conf && \
    a2enconf security-hardening

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

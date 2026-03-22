FROM php:8.2-apache

RUN docker-php-ext-install mysqli

RUN a2dismod mpm_event -f || true && \
    a2dismod mpm_worker -f || true && \
    a2dismod mpm_async -f || true && \
    a2enmod mpm_prefork && \
    rm -f /etc/apache2/mods-enabled/mpm_event.* && \
    rm -f /etc/apache2/mods-enabled/mpm_worker.* && \
    rm -f /etc/apache2/mods-enabled/mpm_async.*

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

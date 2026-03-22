FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Remove ALL mpm modules completely to ensure clean slate
RUN find /etc/apache2/mods-enabled -name 'mpm_*.load' -delete && \
    find /etc/apache2/mods-enabled -name 'mpm_*.conf' -delete && \
    find /etc/apache2/mods-available -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete && \
    find /etc/apache2/mods-available -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete && \
    a2enmod mpm_prefork

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

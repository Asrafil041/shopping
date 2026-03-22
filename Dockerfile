FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Ensure only mpm_prefork is active — Apache cannot load more than one MPM
RUN a2dismod mpm_event mpm_worker mpm_itk 2>/dev/null || true && \
    a2enmod mpm_prefork

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

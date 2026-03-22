FROM php:8.2-apache-bookworm

# Force rebuild by making this layer depend on current timestamp
ARG BUILD_DATE
RUN echo "Build date: $BUILD_DATE"

# Install PHP extensions and fix MPM configuration in ONE RUN layer to prevent
# Docker from caching any intermediate state where multiple MPMs exist.
RUN docker-php-ext-install mysqli && \
    # Disable ALL conflicting MPM modules FIRST
    a2dismod mpm_event mpm_worker mpm_itk mpm_async || true && \
    # Remove mpm_event.load and mpm_event.conf files EXPLICITLY by path
    rm -fv /etc/apache2/mods-enabled/mpm_event.* && \
    rm -fv /etc/apache2/mods-enabled/mpm_worker.* && \
    rm -fv /etc/apache2/mods-enabled/mpm_itk.* && \
    rm -fv /etc/apache2/mods-enabled/mpm_async.* && \
    # Remove from mods-available as well to prevent re-enabling
    rm -fv /etc/apache2/mods-available/mpm_event.* && \
    rm -fv /etc/apache2/mods-available/mpm_worker.* && \
    rm -fv /etc/apache2/mods-available/mpm_itk.* && \
    rm -fv /etc/apache2/mods-available/mpm_async.* && \
    # NOW enable ONLY mpm-prefork
    a2enmod mpm_prefork && \
    # Verify the result
    echo "=== Dockerfile: Final MPM module state ===" && \
    echo "mods-enabled:" && \
    ls -1 /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || echo "  (only prefork should exist)" && \
    echo "mods-available:" && \
    ls -1 /etc/apache2/mods-available/mpm_*.load

# Create custom Apache config to FORCE only mpm_prefork and prevent mpm_event from loading
RUN echo "# Force mpm_prefork to load FIRST, explicitly exclude mpm_event" > /etc/apache2/conf-enabled/mpm-override.conf && \
    echo "# This ensures mpm_prefork is the ONLY active MPM" >> /etc/apache2/conf-enabled/mpm-override.conf && \
    echo "# Even if mpm_event symlinks somehow exist, they will not be honored" >> /etc/apache2/conf-enabled/mpm-override.conf && \
    cat /etc/apache2/mods-enabled/mpm_prefork.load >> /etc/apache2/conf-enabled/mpm-override.conf

# Enable PHP error logging to stdout for debugging
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

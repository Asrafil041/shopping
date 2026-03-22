FROM php:8.2-apache-bookworm

# Install PHP extensions and fix MPM configuration in a single RUN layer.
# This ensures Docker cannot cache an intermediate state where multiple MPMs are present.
RUN docker-php-ext-install mysqli && \
    # Step 1: Remove ALL mpm modules from mods-available (except prefork)
    find /etc/apache2/mods-available -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete && \
    find /etc/apache2/mods-available -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete && \
    # Step 2: Remove ALL mpm module symlinks from mods-enabled
    find /etc/apache2/mods-enabled -type l \( -name 'mpm_*.load' -o -name 'mpm_*.conf' \) -delete && \
    # Step 3: Disable all mpm modules as safety net
    a2dismod mpm_event mpm_worker mpm_itk mpm_async || true && \
    # Step 4: Explicitly enable only mpm_prefork
    a2enmod mpm_prefork && \
    # Step 5: Verify the result in build logs
    echo "=== Final MPM state in mods-enabled ===" && \
    ls -la /etc/apache2/mods-enabled/mpm_* 2>/dev/null || echo "  (only prefork should exist, others deleted)"

# Enable PHP error logging to stdout for debugging
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

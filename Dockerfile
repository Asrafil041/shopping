FROM php:8.2-apache-bookworm

# Install PHP extensions and fix MPM configuration in a single RUN layer so
# Docker cannot cache an intermediate state where mpm_event is still present.
#
# Step 1 — install mysqli extension.
# Step 2 — use `find -type l` to explicitly remove only the mpm_* symlinks
#           from mods-enabled (rm -f can miss symlinks; find -type l cannot).
# Step 3 — run a2dismod as a safety net in case any symlinks were re-created.
# Step 4 — explicitly enable mpm_prefork.
# Step 5 — list mods-enabled so the build log confirms the final state.
RUN docker-php-ext-install mysqli && \
    find /etc/apache2/mods-enabled \( -type l -name 'mpm_*.load' -o -type l -name 'mpm_*.conf' \) -delete && \
    a2dismod mpm_event mpm_worker mpm_itk || true && \
    a2enmod mpm_prefork && \
    echo "--- mods-enabled after MPM cleanup ---" && \
    ls -la /etc/apache2/mods-enabled/

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

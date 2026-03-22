FROM php:8.2-apache-bookworm

# Install PHP extensions and fix MPM configuration in a single RUN layer so
# Docker cannot cache an intermediate state where mpm_event is still present.
#
# Step 1 — install mysqli extension.
# Step 2 — disable mpm_event via a2dismod (|| true so it never hard-fails).
# Step 3 — remove symlinks from mods-enabled for every non-prefork MPM.
# Step 4 — delete the actual .load/.conf files from mods-available so
#           nothing can re-enable them (not even a2enmod).
# Step 5 — explicitly enable mpm_prefork.
RUN docker-php-ext-install mysqli && \
    a2dismod mpm_event mpm_worker mpm_itk || true && \
    rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
          /etc/apache2/mods-enabled/mpm_itk.load \
          /etc/apache2/mods-enabled/mpm_itk.conf && \
    rm -f /etc/apache2/mods-available/mpm_event.load \
          /etc/apache2/mods-available/mpm_event.conf \
          /etc/apache2/mods-available/mpm_worker.load \
          /etc/apache2/mods-available/mpm_worker.conf \
          /etc/apache2/mods-available/mpm_itk.load \
          /etc/apache2/mods-available/mpm_itk.conf && \
    a2enmod mpm_prefork

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

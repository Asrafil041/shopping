FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Aggressively remove all MPM modules except prefork so Apache cannot
# accidentally load more than one MPM at startup.
#
# Step 1 — remove symlinks from mods-enabled for every non-prefork MPM.
# Step 2 — delete the actual .load/.conf files from mods-available so
#           nothing can re-enable them (not even a2enmod).
# Step 3 — explicitly enable mpm_prefork.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
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

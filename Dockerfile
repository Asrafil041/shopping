FROM php:8.2-apache

RUN docker-php-ext-install mysqli
RUN a2dismod mpm_event || true
RUN a2enmod mpm_prefork

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/img/artistas/ && \
    chmod -R 775 /var/www/html/img/artistas/

EXPOSE 80
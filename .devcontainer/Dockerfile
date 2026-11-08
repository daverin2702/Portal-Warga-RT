FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql \
 && echo "display_errors=On\nerror_reporting=E_ALL" > /usr/local/etc/php/conf.d/dev.ini

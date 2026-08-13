FROM php:8.2-apache

# Die MySQL/MariaDB-Erweiterung ist im offiziellen PHP-Image nicht
# standardmäßig aktiviert - wird für api.php aber benötigt.
RUN docker-php-ext-install pdo_mysql

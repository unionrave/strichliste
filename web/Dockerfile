FROM php:8.2-apache

# Die MySQL/MariaDB-Erweiterung ist im offiziellen PHP-Image nicht
# standardmäßig aktiviert - wird für api.php aber benötigt.
RUN docker-php-ext-install pdo_mysql

# Gzip-Kompression aktivieren (siehe deflate.conf für Details/Begründung).
COPY deflate.conf /etc/apache2/conf-available/deflate.conf
RUN a2enmod deflate && a2enconf deflate

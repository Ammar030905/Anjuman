FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && printf '%s\n' \
        '<VirtualHost *:80>' \
        '    DocumentRoot /var/www/html' \
        '    <Directory /var/www/html>' \
        '        AllowOverride All' \
        '        Require all granted' \
        '    </Directory>' \
        '</VirtualHost>' \
        > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

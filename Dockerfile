FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/html/logs /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions

RUN printf '%s\n' \
    'memory_limit=256M' \
    'max_execution_time=30' \
    'max_input_time=30' \
    'post_max_size=20M' \
    'upload_max_filesize=16M' \
    'expose_php=0' \
    'session.save_path=/var/lib/php/sessions' \
    'session.gc_maxlifetime=3600' \
    'opcache.enable=1' \
    'opcache.enable_cli=0' \
    'opcache.validate_timestamps=0' \
    'opcache.max_accelerated_files=10000' \
    'opcache.memory_consumption=128' \
    > /usr/local/etc/php/conf.d/99-anjuman-prod.ini

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

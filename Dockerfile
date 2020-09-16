FROM php:7.4-apache

RUN apt-get update -y -qq && \
    apt-get install -y --no-install-recommends \
        libpq-dev \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Install php extensions for PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql sockets

# Enable rewrite module to enable laravel URL routes
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY ./config/000-default.conf /etc/apache2/sites-available/
COPY ./src /var/www/html

# Install composer and initialize application
RUN cd /var/www/html && \
    curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install && \
    chown -R www-data:www-data ./storage && \
    cp .env.example .env && \
    php artisan key:generate

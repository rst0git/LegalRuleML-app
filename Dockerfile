FROM php:7.0-apache

# Update and install PostgreSQL dependencies
RUN apt-get -y -qq update && apt-get install -y libpq-dev

# Install php extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql sockets


# Enable rewrite module to allow URLs from laravel
RUN a2enmod rewrite

# Copy Apache config
COPY ./config/000-default.conf /etc/apache2/sites-available/

# Note: Comment out the lines below to reduce the build time

# Set ownership for Laravel's storage folder
#ADD ./src /var/www/html
#RUN chown -R www-data:www-data /var/www/html/storage

# Migrate Database
#RUN php artisan migrate
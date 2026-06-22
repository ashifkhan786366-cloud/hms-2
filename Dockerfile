FROM php:8.2-apache

# Install system dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions: pdo_pgsql for Aiven PostgreSQL + pdo_mysql for local dev + zip
RUN docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mysqli zip

# Enable Apache rewrite module
RUN a2enmod rewrite headers

# Copy Apache virtual host config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# PHP runtime settings
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/hms.ini

# Copy application files
COPY . /var/www/html/

# Copy entrypoint script and make it executable
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod 775 /var/www/html/uploads

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]

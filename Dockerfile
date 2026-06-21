FROM php:8.1-apache

# ── Install system dependencies with retry logic ──
RUN set -eux; \
    apt-get update -y; \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip; \
    rm -rf /var/lib/apt/lists/*

# ── Install PHP extensions ──
RUN docker-php-ext-install pdo pdo_mysql mysqli zip

# ── Enable Apache mod_rewrite ──
RUN a2enmod rewrite headers

# ── Apache Config ──
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# ── PHP Settings ──
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/hms.ini

# ── Copy App ──
COPY . /var/www/html/

# ── Permissions ──
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 775 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]

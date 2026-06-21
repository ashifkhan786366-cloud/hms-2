FROM php:8.1-apache

# ── System dependencies (Debian Bookworm compatible) ──
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libzip-dev \
    zip \
    unzip \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# ── PHP Extensions ──
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        zip

# ── Enable Apache modules ──
RUN a2enmod rewrite headers

# ── Apache Configuration ──
COPY apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# ── PHP Production Settings ──
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/custom.ini

# ── Copy Application Files ──
COPY . /var/www/html/

# ── Permissions ──
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]

FROM php:8.1-apache

# ── System dependencies ──
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
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
        zip \
        intl \
        exif

# ── Enable Apache modules ──
RUN a2enmod rewrite headers expires deflate

# ── Apache Configuration ──
COPY apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# ── PHP Configuration for Production ──
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/custom.ini

# ── Copy Application Files ──
COPY . /var/www/html/

# ── Set Permissions ──
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && find /var/www/html -name "*.php" -exec chmod 644 {} \;

# ── Remove sensitive local config if exists ──
RUN rm -f /var/www/html/config/db.php.local

EXPOSE 80

CMD ["apache2-foreground"]

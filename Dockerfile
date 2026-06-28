# syntax=docker/dockerfile:1

# ============================================================
# Stage 1 – Composer dependency install
# ============================================================
FROM composer:2.8 AS composer-deps

WORKDIR /build

# Copy only the manifest files first for better layer caching
COPY ["DIHS Form Management System/composer.json", "DIHS Form Management System/composer.lock", "./"]

# Install PHP production dependencies (no dev, no scripts)
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-interaction \
      --prefer-dist \
      --optimize-autoloader

# ============================================================
# Stage 2 – Runtime image
# ============================================================
FROM php:8.2-apache

# --------------- System packages & PHP extensions -----------

# Install OS-level libraries required by extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
      # zip / zlib
      libzip-dev \
      zlib1g-dev \
      # GD (image processing for dompdf / phpspreadsheet charts)
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      # intl
      libicu-dev \
      # curl
      libcurl4-openssl-dev \
      # xml / iconv / mbstring (already in base, but libs needed)
      libxml2-dev \
      # Node.js (for npm install)
      nodejs \
      npm \
      # Misc utilities
      unzip \
      git \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with JPEG + FreeType support
RUN docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg

# Install all required PHP extensions in one layer
RUN docker-php-ext-install -j"$(nproc)" \
      mysqli \
      pdo \
      pdo_mysql \
      zip \
      gd \
      intl \
      curl \
      mbstring \
      xml \
      xmlreader \
      xmlwriter \
      simplexml \
      dom \
      iconv \
      fileinfo \
      opcache

# Enable Apache mod_rewrite (needed for clean URLs)
RUN a2enmod rewrite

# --------------- PHP runtime configuration ------------------

RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
      echo 'upload_max_filesize=32M'; \
      echo 'post_max_size=32M'; \
      echo 'memory_limit=256M'; \
      echo 'max_execution_time=120'; \
      echo 'date.timezone=Asia/Manila'; \
    } > /usr/local/etc/php/conf.d/app.ini

# --------------- Apache virtual host configuration ----------

# Set the document root to the app subdirectory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/DIHS\ Form\ Management\ System

RUN sed -ri \
      's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
      /etc/apache2/sites-available/000-default.conf \
    && sed -ri \
      's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
      /etc/apache2/apache2.conf

# Allow .htaccess overrides and set DirectoryIndex
RUN sed -ri \
      's|AllowOverride None|AllowOverride All|g' \
      /etc/apache2/apache2.conf

# Write a minimal .htaccess so the root URL lands on login/index.php
RUN mkdir -p "/var/www/html/DIHS Form Management System" && \
    printf 'DirectoryIndex login/index.php index.php index.html\n\
Options -Indexes\n\
\n\
<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteBase /\n\
    # Redirect bare root to login page\n\
    RewriteRule ^$ login/index.php [L,R=302]\n\
</IfModule>\n' \
    > "/var/www/html/DIHS Form Management System/.htaccess"

# --------------- Copy application files ---------------------

WORKDIR /var/www/html

# Copy the entire app directory
COPY ["DIHS Form Management System", "DIHS Form Management System/"]

# Overlay the vendor directory built in stage 1
COPY --from=composer-deps /build/vendor \
     "DIHS Form Management System/vendor/"

# --------------- Node / npm dependencies --------------------

# Install Node dependencies (dev tools: jest/babel — used for CI tests)
WORKDIR "/var/www/html/DIHS Form Management System"
RUN npm install --prefer-offline 2>/dev/null || npm install

# --------------- Permissions --------------------------------

WORKDIR /var/www/html

# Give Apache ownership of the app files
RUN chown -R www-data:www-data \
      "DIHS Form Management System" \
    && find "DIHS Form Management System" -type d -exec chmod 755 {} \; \
    && find "DIHS Form Management System" -type f -exec chmod 644 {} \;

# --------------- Expose & start -----------------------------

EXPOSE 80

CMD ["apache2-foreground"]

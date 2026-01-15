# Stage 1: Build Backend Dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Install dependencies ignoring platform requirements (often necessary in docker)
# and without dev dependencies (for production size)
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

# Stage 2: Build Frontend Assets
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
COPY --from=vendor /app/vendor/ /app/vendor/
RUN npm run build

# Stage 3: Production Image
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www

# Remove default server definition
RUN rm -rf /var/www/html

# Copy backend dependencies
COPY --from=vendor /app/vendor/ /var/www/vendor/

# Copy frontend assets (built files)
COPY --from=frontend /app/public/build/ /var/www/public/build/

# Copy application code
COPY . /var/www

# Fix permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Expose port
EXPOSE 9000

ENTRYPOINT ["entrypoint"]

# ======================
# BASE
# ======================
FROM node:24-alpine AS base
WORKDIR /app
RUN corepack enable && corepack prepare pnpm@latest --activate


# ======================
# DEV: NODE
# ======================
FROM base AS node_dev

WORKDIR /app

# hanya copy package untuk cache layer
COPY package.json pnpm-lock.yaml* ./


# ======================
# DEV: PHP
# ======================
FROM php:8.4-fpm AS php_dev

RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libonig-dev \
  libxml2-dev \
  libicu-dev \
  libzip-dev \
  zip \
  unzip \
  curl

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /app


# ======================
# BUILD NODE (PROD)
# ======================
FROM base AS node_builder

COPY package.json pnpm-lock.yaml* ./
RUN pnpm install

COPY . .
RUN pnpm run build


# ======================
# PROD: PHP
# ======================
FROM php:8.4-fpm AS production

RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libonig-dev \
  libxml2-dev \
  libicu-dev \
  libzip-dev \
  zip \
  unzip \
  curl

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Copy hasil build Vite
COPY --from=node_builder /app/public/build /app/public/build

# Prepare Laravel
RUN mkdir -p \
  storage/framework/views \
  storage/framework/cache \
  storage/framework/sessions \
  storage/logs \
  storage/app/private \
  storage/app/public \
  bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader

RUN php artisan storage:link || true
RUN php artisan optimize:clear
RUN php artisan route:cache
RUN php artisan view:cache

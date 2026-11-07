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
FROM php:8.5.12-fpm AS php_dev

RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libwebp-dev \
  libfreetype6-dev \
  libonig-dev \
  libxml2-dev \
  libicu-dev \
  libzip-dev \
  zip \
  unzip \
  curl \
  bash \
  gzip \
  default-mysql-client

RUN docker-php-ext-configure intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2.11.1 /usr/bin/composer /usr/bin/composer

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
FROM php:8.5.12-fpm AS production

RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libwebp-dev \
  libfreetype6-dev \
  libonig-dev \
  libxml2-dev \
  libicu-dev \
  libzip-dev \
  zip \
  unzip \
  curl \
  bash \
  gzip \
  default-mysql-client

RUN docker-php-ext-configure intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2.11.1 /usr/bin/composer /usr/bin/composer

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
# .env tidak ikut ke image (lihat .dockerignore) — pakai cache store "array"
# agar cache:clear tidak mencoba konek ke database saat build.
RUN CACHE_STORE=array php artisan optimize:clear
# Jangan route:cache di sini: tanpa .env, APP_KEY kosong, padahal prefix endpoint Livewire
# (/livewire-<hash>/update) diturunkan dari APP_KEY. Route cache hasil build akan memuat
# prefix yang salah, sehingga semua request Livewire 404. Route di-cache saat deploy
# (`php artisan optimize` di dalam container, .env sudah ter-mount).
RUN php artisan view:cache

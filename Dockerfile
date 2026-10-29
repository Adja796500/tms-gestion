# Image de production de la plateforme (utilisee par Render, ou tout hebergeur Docker).

# 1) Compilation du design (Tailwind / Vite)
FROM node:20-alpine AS design
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY app ./app
RUN npm run build

# 2) Serveur PHP 8.2 + Apache
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip gd intl mbstring opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache sert le dossier public/ de Laravel, sur le port fourni par l'hebergeur
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=design /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/demarrage.sh

ENV PORT=10000
EXPOSE 10000
CMD ["docker/demarrage.sh"]

# Stage 1: Install Composer dependencies
FROM php:8.4-fpm AS build

WORKDIR /usr/src/app

RUN apt-get update && apt-get install -y \
    unzip zip git curl libzip-dev libpng-dev libjpeg-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY . .

ARG INSTALL_DEV=false
RUN if [ "$INSTALL_DEV" = "true" ]; then \
    composer install; \
else \
    composer install --no-dev --optimize-autoloader; \
fi

# Stage 2: Final image
FROM php:8.4-fpm

WORKDIR /usr/src/app

RUN apt-get update && apt-get install -y \
        libpng-dev libjpeg-dev libpq-dev libzip-dev libxml2-dev libonig-dev \
    && pecl install redis \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql gd exif opcache pcntl zip \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=build /usr/src/app /usr/src/app

COPY .docker/php/laravel.ini /usr/local/etc/php/conf.d/laravel.ini

COPY .docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ARG INSTALL_DEV=false
RUN if [ "$INSTALL_DEV" = "true" ]; then \
    pecl install xdebug && docker-php-ext-enable xdebug; \
fi

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

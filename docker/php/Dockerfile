FROM php:8.4-fpm-alpine

# Dependencias de sistema y librerías para las extensiones PHP
RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        intl \
        bcmath \
        zip \
        gd \
        exif \
        pcntl \
    && apk del .build-deps

# Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# UID/GID configurables para evitar problemas de permisos con el volumen del host
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app 2>/dev/null || true \
    && adduser -D -u ${UID} -G app app 2>/dev/null || adduser -D -u ${UID} app

WORKDIR /var/www/html

# Entrypoint: instala dependencias, prepara el storage y espera a la DB
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

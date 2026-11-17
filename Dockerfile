FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpq-dev \
    libzip-dev \
    linux-headers \
    oniguruma-dev \
    unzip \
    zip \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
         opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        sockets \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www

COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

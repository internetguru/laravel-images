FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libjpeg-dev \
    libpng-dev \
    libwebp-dev \
    libavif-dev

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --prefer-dist --no-interaction

CMD ["./vendor/bin/phpunit"]

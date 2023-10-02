FROM php:8.2.11-fpm
WORKDIR /app
RUN apt-get update \
        && apt-get -y install libgmp-dev zlib1g-dev libfreetype6-dev \
            libjpeg62-turbo-dev libpng-dev libzip-dev \
        && docker-php-ext-install gd gmp zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ADD ../../www ./
COPY ./docker/php/docker-zstore-exts.ini /usr/local/etc/php/conf.d/
RUN composer update
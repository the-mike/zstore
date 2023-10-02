FROM php:8.2.11-fpm
WORKDIR /var/www/html
RUN apt-get update \
        && apt-get -y install libgmp-dev zlib1g-dev libfreetype6-dev \
            libjpeg62-turbo-dev libpng-dev libzip-dev libpq-dev \
        && docker-php-ext-install gd gmp zip pgsql pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ADD ../../www ./
RUN composer update

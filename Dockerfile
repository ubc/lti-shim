FROM composer AS composer
WORKDIR /var/www
COPY . /var/www/
RUN composer install --no-dev --ignore-platform-reqs


FROM php:7.3-fpm
WORKDIR /var/www

RUN apt-get update && apt-get -y install git && apt-get -y install zip libgmp-dev \
        && docker-php-ext-install -j$(nproc) gmp

COPY . /var/www
COPY --from=composer /var/www/vendor /var/www/vendor
COPY --from=composer /var/www/bootstrap /var/www/bootstrap

RUN chown -R www-data:www-data \
        /var/www/storage \
        /var/www/bootstrap/cache

RUN  apt-get install -y libmcrypt-dev \
        --no-install-recommends \
        && pecl install mcrypt-1.0.2 \
        && docker-php-ext-enable mcrypt

RUN php artisan optimize

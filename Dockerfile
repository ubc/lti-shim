# install backend PHP dependencies
FROM composer AS composer
WORKDIR /var/www
# copy composer files first so docker knows that they're dependencies for the
# composer install step and thus will cache the composer install step unless
# the composer files has changes.
COPY composer.json /var/www/
COPY composer.lock /var/www/
# Note: we can't generate the autoloader because that needs other project files
# that hasn't been copied yet
RUN composer install --no-dev --ignore-platform-reqs --no-autoloader \
    --prefer-dist --no-progress
# once we have the rest of the project, we can generate the autoloader
COPY . /var/www/
RUN composer dump-autoload --optimize


# install & compile frontend JS/CSS
FROM node:14-alpine AS node
WORKDIR /app
# copy package.json first so docker knows it can cache the npm install step
COPY package.json package-lock.json webpack.mix.js /app/
RUN npm install
# NOTE: 'npm run production' doesn't just make changes in public/ so we need to
# let it run on the entire project, and the final image must copy from this.
COPY --from=composer /var/www/ /app/
RUN npm run production


# actual image we'll run in the end
FROM php:7.4-apache
WORKDIR /var/www

RUN apt-get update -yqq && \
    # apt-utils needed for package configuration
    apt-get install -yqq apt-utils  && \
    # update php repos
    pecl channel-update pecl.php.net && \
    apt-get install -yqq git zip

# mcrypt for password hashing, I think
RUN apt-get install -y --no-install-recommends libmcrypt-dev
# php postgres driver
RUN apt-get install -y --no-install-recommends libpq-dev && \
    docker-php-ext-install pdo_pgsql
# php gmp extension required by JWT Framework library for encryption
RUN apt-get install -y libgmp-dev && \
    docker-php-ext-install -j$(nproc) gmp
# php intl extension used by League\Uri for unicode URLs
RUN apt-get install -y libicu-dev && \
    docker-php-ext-install intl
# for entrypoint, so we can wait for the database before running migrations
RUN apt-get install -y wait-for-it
# enable apache mod_rewrite or the laravel router won't work
RUN a2enmod rewrite

COPY --from=node /app/ /var/www/
# change document root from the default /var/www/html to /var/www/public
ENV APACHE_DOCUMENT_ROOT /var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# make sure we have a laravel env file
COPY deploy-docker/deploy-env /var/www/.env

RUN chown -R www-data:www-data \
        /var/www/storage \
        /var/www/bootstrap/cache

# combine config into a single file
RUN php artisan config:cache
# combine routes into a single file
RUN php artisan route:cache
# precompile views ahead of time instead of on-the-fly
RUN php artisan view:cache

# custom entrypoint so that we can run things like database migrations
COPY deploy-docker/custom-entrypoint.sh /usr/local/bin/custom-entrypoint
ENTRYPOINT ["custom-entrypoint", "docker-php-entrypoint"]
CMD ["apache2-foreground"]

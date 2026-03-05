FROM php:8.5-fpm-trixie AS php-fpm-base

WORKDIR /pim/

RUN apt-get update && \
    apt-get install -y git \
                       unzip \
                       libpq-dev \
                       libicu-dev \
                       libzip-dev && \
    groupadd --gid 1000 pim-user  && \
    useradd --uid 1000 --gid pim-user --shell /bin/bash --create-home pim-user && \
    docker-php-ext-configure intl && \
    docker-php-ext-install zip \
                           pdo_pgsql \
                           pgsql \
                           intl && \
    docker-php-ext-enable intl


ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

FROM php-fpm-base AS php-fpm-dev

COPY ./docker/fpm/dev/php-ini-overrides.ini $PHP_INI_DIR/conf.d/php.ini

RUN pecl install xdebug-3.5.0 && \
    docker-php-ext-enable xdebug

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/composer
COPY ./docker/fpm/dev/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

RUN apt-get -y autoremove && apt-get -y autoclean

COPY ./docker/fpm/dev/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

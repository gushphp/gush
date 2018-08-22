FROM php:7.2.8-cli-alpine

RUN set -xe \
    && apk add --no-cache \
    git \
    openssh-client

COPY --from=composer:1.6 /usr/bin/composer /usr/bin/composer
COPY ./src /usr/src/gush/src
COPY ./gush /usr/src/gush/gush
COPY ./composer.json /usr/src/gush/composer.json

WORKDIR /usr/src/gush

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --prefer-dist --optimize-autoloader --no-interaction --no-dev \
    && rm composer.json \
    && rm composer.lock

RUN mkdir /root/project

WORKDIR /root/project

ENTRYPOINT ["/usr/src/gush/gush"]

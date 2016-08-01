FROM php:7.0.8-alpine

COPY . /usr/src/gush
WORKDIR /usr/src/gush

RUN set -xe \
    && apk add --no-cache git \
    && curl -s https://getcomposer.org/installer | php \
    && chmod +x composer.phar && mv composer.phar /usr/bin/composer \
    && composer install

ENTRYPOINT ["/usr/src/gush/gush"]

FROM php:7.0.8-alpine

RUN set -xe \
    && apk add --no-cache \
        git

RUN curl -s https://getcomposer.org/installer | php && \
    chmod +x composer.phar && \
    mv composer.phar /usr/bin/composer

COPY . /usr/src/gush
WORKDIR /usr/src/gush

RUN composer install

ENTRYPOINT ["/usr/src/gush/gush"]
CMD ["--help"]

FROM php:7.0.7-fpm

RUN apt-get update && apt-get install -y \
    zip \
    build-essential \
    sudo \
    git \
    nano \
    apt-utils \
    libpq-dev \

RUN curl -s https://getcomposer.org/installer | php && \
    chmod +x composer.phar && \
    mv composer.phar /usr/bin/composer

WORKDIR /var/www
RUN rm -rf /var/www/*
ADD . /var/www

ADD ./start.sh start.sh

RUN composer install

VOLUME ["/var/www"]

CMD ["/start.sh"]

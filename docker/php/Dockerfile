FROM php:7.4.9-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
  &&  apt-get install -y default-mysql-client zlib1g-dev libzip-dev unzip netcat \
  && docker-php-ext-install pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer \
  && composer self-update

WORKDIR /var/www
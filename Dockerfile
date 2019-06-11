FROM php:7.2-cli-alpine

RUN apk --update add git composer

RUN docker-php-ext-install pdo_mysql

RUN mkdir -p /reaper
ADD . /reaper
WORKDIR /reaper
RUN composer install
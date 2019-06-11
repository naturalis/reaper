FROM php:7.2-cli-alpine

RUN apk --update add git composer

RUN mkdir -p /reaper
ADD . /reaper
WORKDIR /reaper
RUN composer install
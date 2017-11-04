FROM php:7.0

RUN apt-get update && apt-get install unzip git libxml2-dev -y

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

ADD . /code
WORKDIR /code

RUN echo "memory_limit = -1" >> /etc/php.ini
RUN composer install --no-interaction


CMD php ./src/run.php --data=/data
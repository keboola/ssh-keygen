FROM php:7.0

RUN apt-get update && apt-get install unzip git libxml2-dev -y

WORKDIR /home

# Initialize
COPY . /home/

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-interaction

ENTRYPOINT php ./src/run.php --data=/data
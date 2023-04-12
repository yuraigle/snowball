FROM ubuntu:22.04
RUN apt-get update && apt-get install -y nginx php-fpm
RUN apt-get clean

CMD "php -S localhost:8000"


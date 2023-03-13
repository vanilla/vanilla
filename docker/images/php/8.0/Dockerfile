FROM php:8.0-fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd xdebug apcu memcached opcache exif mbstring pdo pdo_mysql intl
RUN apt-get update && apt-get -y install rsyslog

COPY ./rsyslog.conf /etc/rsyslog.conf
COPY ./php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./start-container.sh /start-container.sh
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

CMD ["/start-container.sh"]

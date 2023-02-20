FROM php:7.4.6-fpm-alpine

# For the iconv fix below.
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

RUN apk update \
    # Make sure we use a version of iconv that works.
    # https://github.com/docker-library/php/issues/240#issuecomment-876464325
    && apk add gnu-libiconv=1.15-r3 --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.13/community/ --allow-untrusted \
    && apk add --no-cache \
        # build
        gcc make musl-dev php7-dev \
        # for intl extension
        icu \
        icu-dev \
        # for gd
        freetype libjpeg-turbo libpng libwebp libxpm \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev \
        # for mbstring
        oniguruma \
        oniguruma-dev \
        # memchached extension
        libmemcached \
        libmemcached-dev \
        # syslogd
        rsyslog \
    # pecl installs
    && pecl install apcu \
    && pecl install memcached \
    && pecl install xdebug \
    # enable pecl installed extentions
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable memcached \
    && docker-php-ext-enable opcache \
    && docker-php-ext-enable xdebug \
    # built in extensions install
    && docker-php-ext-configure gd \
        --enable-gd \
        --with-webp \
        --with-jpeg \
        --with-xpm \
        --with-freetype \
    && docker-php-ext-install -j$(nproc) \
        exif \
        gd \
        mbstring \
        pdo \
        pdo_mysql \
        intl \
    # cleanup
    && apk del \
        gcc make musl-dev php7-dev \
        icu-dev \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev \
        oniguruma-dev \
        libmemcached-dev \
    && pecl clear-cache

RUN sed -i '/imklog/s/^/#/' /etc/rsyslog.conf
RUN touch /var/log/syslog

COPY ../php.ini /usr/local/etc/php/conf.d/php.ini
COPY ../www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY ../start-container.sh /start-container.sh

CMD ["/start-container.sh"]

FROM vanillaforums/php:8.0

ARG WWWGROUP=1000
ARG WWWUSER=1000

RUN groupmod -o -g $WWWGROUP www-data
RUN usermod -o -u $WWWUSER -g $WWWGROUP www-data

RUN mkdir /var/log/xdebug && chown -R www-data:www-data /var/log/xdebug && chmod 0777 /var/log/xdebug
COPY ./rsyslog.conf /etc/rsyslog.conf
COPY ./php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./start-container.sh /start-container.sh
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY ./certs/vanilla.local.crt /usr/local/share/ca-certificates/vanilla.local.crt
COPY ./certs/wildcard.vanilla.local.crt /usr/local/share/ca-certificates/wildcard.vanilla.local.crt
RUN chmod 644 /usr/local/share/ca-certificates/vanilla.local.crt \
    && chmod 644 /usr/local/share/ca-certificates/wildcard.vanilla.local.crt \
    && update-ca-certificates

CMD ["/start-container.sh"]

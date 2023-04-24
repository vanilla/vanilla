FROM vanillaforums/php:8.0

RUN mkdir /var/log/xdebug && chown -R www-data:www-data /var/log/xdebug && chmod 0777 /var/log/xdebug
COPY ./rsyslog.conf /etc/rsyslog.conf
COPY ./php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./start-container.sh /start-container.sh
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

CMD ["/start-container.sh"]

#!/bin/sh

# Start rsyslogd
rsyslogd

mkdir /var/log/xdebug && chown -R www-data

# Truncate the syslog
cat /dev/null > /var/log/messages

if [[ -z "${PHP_DEBUG}" ]]; then
  php-fpm -d xdebug.mode="off" &
else
  php-fpm -d xdebug.idekey="PHPSTORM" \
        -d xdebug.mode="debug" \
        -d xdebug.start_with_request="yes" \
        -d xdebug.output_dir="/var/log/xdebug" \
        -d xdebug.profiler_output_name="trace.%R" \
        -d xdebug.log="/var/log/xdebug/www.log" \
        -d xdebug.client_host="host.docker.internal" &
fi
export PHP_FPM_PID=$!

_exit() {
  kill -QUIT "$PHP_FPM_PID"
  exit 0
}

trap _exit SIGTERM SIGQUIT

wait "$PHP_FPM_PID"

#!/bin/sh

# Start rsyslogd
rsyslogd

# Truncate the syslog
cat /dev/null > /var/log/messages

php-fpm -d xdebug.idekey="PHPSTORM" \
    -d xdebug.mode="debug" \
    -d xdebug.start_with_request="trigger" \
    -d xdebug.output_dir="/var/log/xdebug" \
    -d xdebug.profiler_output_name="trace.%R" \
    -d xdebug.log="/var/log/xdebug/www.log" \
    -d xdebug.client_host="host.docker.internal" &
export PHP_FPM_PID=$!

_exit() {
  kill -QUIT "$PHP_FPM_PID"
  exit 0
}

trap _exit TERM QUIT

wait "$PHP_FPM_PID"

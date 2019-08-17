#!/bin/bash

set -e
set -x

DIR=$(realpath $(dirname "$0"))
TEMPLATES="$DIR/templates"
USER=$(whoami)
PHP_VERSION=$(phpenv version-name)
ROOT=$(realpath "$DIR/../..")
PORT=9000
SERVER="/tmp/php.sock"

function tpl {
    sed \
        -e "s|{DIR}|$DIR|g" \
        -e "s|{USER}|$USER|g" \
        -e "s|{PHP_VERSION}|$PHP_VERSION|g" \
        -e "s|{ROOT}|$ROOT|g" \
        -e "s|{PORT}|$PORT|g" \
        -e "s|{SERVER}|$SERVER|g" \
        < $1 > $2
}

# Make some working directories.
mkdir "$DIR/nginx"
mkdir "$DIR/nginx/sites-enabled"
mkdir "$DIR/php"
mkdir "$DIR/var"

# Build the default nginx config files.
tpl "$TEMPLATES/nginx/nginx.conf.tpl" "$DIR/nginx/nginx.conf"
tpl "$TEMPLATES/nginx/fastcgi.conf.tpl" "$DIR/nginx/fastcgi.conf"
tpl "$TEMPLATES/nginx/sites-enabled/default-site.conf.tpl" "$DIR/nginx/sites-enabled/default-site.conf"

# Configure the PHP handler.
PHP_FPM_BIN="$HOME/.phpenv/versions/$PHP_VERSION/sbin/php-fpm"
PHP_FPM_CONF="$DIR/php/php-fpm.conf"

# Build the php-fpm.conf.
tpl "$TEMPLATES/php/php-fpm.conf.tpl" "$PHP_FPM_CONF"

# Start php-fpm
"$PHP_FPM_BIN" --fpm-config "$PHP_FPM_CONF"

# Copy the config changer that will allow Vanilla to use a different config file per host.
cp "$TEMPLATES/vanilla/conf/bootstrap.before.php" "$ROOT/conf"

# Start nginx.
nginx -c "$DIR/nginx/nginx.conf"

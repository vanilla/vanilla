#!/bin/bash

set -e
set -x

# Start php-fpm
php-fpm --daemonize

DIR=$(realpath $(dirname "$0"))
TEMPLATES="$DIR/templates"
ROOT=$(realpath "$DIR/../..")
PORT=9000
SERVER="/tmp/php.sock"

function tpl {
    sudo sed \
        -e "s|{DIR}|$DIR|g" \
        -e "s|{ROOT}|$ROOT|g" \
        -e "s|{PORT}|$PORT|g" \
        -e "s|{SERVER}|$SERVER|g" \
        < $1 > $2
}

# Make some working directories.
sudo mkdir "$DIR/nginx"
sudo mkdir "$DIR/nginx/sites-enabled"

# Build the default nginx config files.
tpl "$TEMPLATES/nginx/nginx.conf.tpl" "$DIR/nginx/nginx.conf"
tpl "$TEMPLATES/nginx/fastcgi.conf.tpl" "$DIR/nginx/fastcgi.conf"
tpl "$TEMPLATES/nginx/sites-enabled/default-site.conf.tpl" "$DIR/nginx/sites-enabled/default-site.conf"

# Copy the config changer that will allow Vanilla to use a different config file per host.
sudo cp "$TEMPLATES/vanilla/conf/bootstrap.before.php" "$ROOT/conf"

# Start nginx.
nginx -c "$DIR/nginx/nginx.conf"

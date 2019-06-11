#!/bin/bash

set -e
set -x

DIR=$(realpath $(dirname "$0"))
TEMPLATES="$DIR/templates"
ROOT=$(realpath "$DIR/../..")
PORT=9000
SERVER="/tmp/php.sock"

function tpl {
    sed \
        -e "s|{DIR}|$DIR|g" \
        -e "s|{ROOT}|$ROOT|g" \
        -e "s|{PORT}|$PORT|g" \
        -e "s|{SERVER}|$SERVER|g" \
        < $1 > $2
}

# Make some working directories.
mkdir "$DIR/nginx"
mkdir "$DIR/nginx/sites-enabled"

# Build the default nginx config files.
tpl "$TEMPLATES/nginx/nginx.conf.tpl" "$DIR/nginx/nginx.conf"
tpl "$TEMPLATES/nginx/fastcgi.conf.tpl" "$DIR/nginx/fastcgi.conf"
tpl "$TEMPLATES/nginx/sites-enabled/default-site.conf.tpl" "$DIR/nginx/sites-enabled/default-site.conf"

# Copy the config changer that will allow Vanilla to use a different config file per host.
cp "$TEMPLATES/vanilla/conf/bootstrap.before.php" "$ROOT/conf"
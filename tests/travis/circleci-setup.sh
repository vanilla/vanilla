#!/bin/bash

set -e
set -x

# Make some working directories.
mkdir "$DIR/nginx"
mkdir "$DIR/nginx/sites-enabled"

# Build the default nginx config files.
tpl "$TEMPLATES/nginx/nginx.conf.tpl" "$DIR/nginx/nginx.conf"
tpl "$TEMPLATES/nginx/fastcgi.conf.tpl" "$DIR/nginx/fastcgi.conf"
tpl "$TEMPLATES/nginx/sites-enabled/default-site.conf.tpl" "$DIR/nginx/sites-enabled/default-site.conf"

# Start php-fpm
php-fpm --daemonize

# Copy the config changer that will allow Vanilla to use a different config file per host.
cp "$TEMPLATES/vanilla/conf/bootstrap.before.php" "$ROOT/conf"

# Start nginx.
nginx -c "$DIR/nginx/nginx.conf"

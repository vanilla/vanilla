#!/bin/bash

set -e
set -x

# Start php-fpm
php-fpm --daemonize

# Make some working directories.
sudo mkdir "$DIR/nginx"
sudo mkdir "$DIR/nginx/sites-enabled"

# Build the default nginx config files.
sudo tpl "$TEMPLATES/nginx/nginx.conf.tpl" "$DIR/nginx/nginx.conf"
sudo tpl "$TEMPLATES/nginx/fastcgi.conf.tpl" "$DIR/nginx/fastcgi.conf"
sudo tpl "$TEMPLATES/nginx/sites-enabled/default-site.conf.tpl" "$DIR/nginx/sites-enabled/default-site.conf"

# Copy the config changer that will allow Vanilla to use a different config file per host.
sudo cp "$TEMPLATES/vanilla/conf/bootstrap.before.php" "$ROOT/conf"

# Start nginx.
nginx -c "$DIR/nginx/nginx.conf"

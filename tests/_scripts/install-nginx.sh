#!/bin/bash

set -e

echo "Installing NGINX for Travis"

DIR=$(dirname "$0")
USER=$(whoami)

sudo apt-get update -qq
sudo apt-get install -yqq nginx realpath
sudo service nginx stop

ROOT_PATH=$(realpath "$DIR/../vanilla")
PHP_FPM_SOCK=$(realpath "$DIR")/php-fpm.sock

# php-fpm configuration

PHP_FPM_BIN="$HOME/.phpenv/versions/$TRAVIS_PHP_VERSION/sbin/php-fpm"
PHP_FPM_CONF="$DIR/php-fpm.conf"

echo "
    [global]

    [travis]
    user = $USER
    group = $USER
    listen = $PHP_FPM_SOCK
    listen.mode = 0666
    pm = static
    pm.max_children = 2

    php_admin_value[memory_limit] = 128M
" > $PHP_FPM_CONF

sudo $PHP_FPM_BIN \
    --fpm-config "$DIR/php-fpm.conf"

# nginx configuration
echo "
	server {
		listen	80;
		root	$ROOT_PATH/;
		index	index.php index.html;

		location ~ \.php {
			fastcgi_pass	unix:$PHP_FPM_SOCK;
			include			fastcgi_params;
		}
	}
" | sudo tee  > /dev/null

echo "Restarting Services"

sudo $PHP_FPM_BIN --fpm-config "$DIR/php-fpm.conf"
sudo service nginx start

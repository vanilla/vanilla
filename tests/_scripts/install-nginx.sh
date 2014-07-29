#!/bin/bash

set -e

echo "Installing NGINX for Travis"

NGINX_CONF="/etc/nginx/sites-enabled/default"
DIR=$(dirname "$0")
USER=$(whoami)

sudo apt-get update -qq
sudo apt-get install -yqq nginx realpath
sudo service nginx stop

ROOT_PATH=$(realpath "$DIR/../../")
PHP_FPM_SOCK=$(realpath "$DIR")/php-fpm.sock

# php-fpm configuration

PHP_FPM_BIN="$HOME/.phpenv/versions/$TRAVIS_PHP_VERSION/sbin/php-fpm"
PHP_FPM_CONF="$DIR/php-fpm.conf"

echo "PHP_FPM_CONF $PHP_FPM_CONF"

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

echo "PHP FPM Conf:"
cat $PHP_FPM_CONF

# nginx configuration
echo "
    server {

        server_name codeception.local;
        listen	80;
        root	$ROOT_PATH/;
        index	index.php index.html;

        # Safeguard against serving configs
        location ~* "/\.htaccess$" { deny all; return 403; }
        location ~* "/\.git" { deny all; return 403; }
        location ~* "/conf/.*$" { deny all; return 403; }
        location ^~ "/favicon.ico" { access_log off; log_not_found off; return 404; }

        # Basic PHP handler
        location ~* "^/index\.php" {
          # send to fastcgi
          fastcgi_pass	unix:$PHP_FPM_SOCK;
          include			fastcgi_params;
       }

       # PHP
       location ~* "^/cgi-bin/.+\.php" {
          root $ROOT_PATH;

          # send to fastcgi
          fastcgi_pass	unix:$PHP_FPM_SOCK;
          include			fastcgi_params;
       }

       # Don't let any other php files run by themselves
       location ~* "\.php" {
          rewrite ^ /index.php?p=\$uri last;
       }

       # Default location
       location / {
          try_files \$uri @vanilla;
       }

       location @vanilla {
          rewrite ^ /index.php?p=\$uri last;
       }

    }
" | sudo tee $NGINX_CONF > /dev/null

echo "NGINX Conf:"
cat $NGINX_CONF

echo "Restarting Services"

sudo $PHP_FPM_BIN --fpm-config "$DIR/php-fpm.conf"
sudo service nginx start

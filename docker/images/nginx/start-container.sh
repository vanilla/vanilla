#!/bin/ash

# Clear out symlinks to stderr and stoud
rm /var/log/nginx/access.log && rm /var/log/nginx/error.log

exec nginx -g 'daemon off;'

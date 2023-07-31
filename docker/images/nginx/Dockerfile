# Pull base image.
FROM nginx:1.15-alpine

COPY ./nginx.conf /etc/nginx/nginx.conf
COPY ./log-format-json.conf /etc/nginx/log-format-json.conf
COPY ./fastcgi.conf /etc/nginx/fastcgi.conf
COPY ./sites-enabled /etc/nginx/sites-enabled
COPY ./partials /etc/nginx/partials
COPY ./certs /certificates

COPY ./start-container.sh /start-container.sh

ENTRYPOINT ["/start-container.sh"]

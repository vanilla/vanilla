FROM vanillaforums/php:8.1

ARG WWWGROUP=1000
ARG WWWUSER=1000

RUN groupmod -o -g $WWWGROUP www-data
RUN usermod -o -u $WWWUSER -g $WWWGROUP www-data

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN \
  # Add Docker's official GPG key:\
    apt-get update && \
    apt-get install -y ca-certificates curl gnupg && \
    install -m 0755 -d /etc/apt/keyrings && \
    curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg && \
    chmod a+r /etc/apt/keyrings/docker.gpg && \
    \
    # Add the repository to Apt sources:
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null && \
    apt-get -y update && \
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
#
#RUN apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

#FROM docker/compose:1.25.5
#WORKDIR /app
#ENTRYPOINT ["/bin/sh"]
CMD /bin/sh -c "trap : TERM INT; sleep 9999999999d & wait"


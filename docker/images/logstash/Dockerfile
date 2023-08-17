ARG ES_STACK_VERSION
FROM docker.elastic.co/logstash/logstash:${ES_STACK_VERSION}
USER root

# Add logstash to the group that owns the php-fpm syslog.
RUN adduser logstash adm


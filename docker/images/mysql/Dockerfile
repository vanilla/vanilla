FROM mysql:8.2

# Make sure we can write to the log directory.
USER root
RUN mkdir -p /var/log/mysql && chown -R mysql /var/log/mysql
COPY ./my.cnf /etc/my.cnf.d/my.cnf
EXPOSE 3306

FROM percona:5.7

# Make sure we can write to the log directory.
USER root
RUN mkdir -p /var/log/mysql && chown -R mysql /var/log/mysql

# Now continue as mysql.
USER mysql
COPY ./my.cnf /etc/my.cnf.d/my.cnf
EXPOSE 3306

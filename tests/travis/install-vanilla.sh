#!/bin/bash

# Create the database for Vanilla.
mysql -utravis -e 'drop database if exists vanilla_test; create database vanilla_test;'

# Install Vanilla. The -f option will make curl return 22 on any HTTP error.
curl -vf \
    -d "Database-dot-Host=localhost" \
    -d "Database-dot-Name=vanilla_test" \
    -d "Database-dot-User=travis" \
    -d "Database-dot-Password=" \
    -d "Garden-dot-Title=Travis" \
    -d "Email=travis@example.com" \
    -d "Name=travis" \
    -d "Password=travis" \
    -d "PasswordMatch=travis" \
    http://vanilla.test:8080/dashboard/setup.json &> /dev/stdout

cat /tmp/error.log
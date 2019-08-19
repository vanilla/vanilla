#!/bin/bash

# Create the database for Vanilla.
mysql -ucircleci -e 'drop database if exists vanilla_test; create database vanilla_test;'

# Install Vanilla. The -f option will make curl return 22 on any HTTP error.
curl -vf \
    -d "Database-dot-Host=localhost" \
    -d "Database-dot-Name=vanilla_test" \
    -d "Database-dot-User=circleci" \
    -d "Database-dot-Password=" \
    -d "Garden-dot-Title=Travis" \
    -d "Email=circleci@example.com" \
    -d "Name=circleci" \
    -d "Password=circleci" \
    -d "PasswordMatch=circleci" \
    http://vanilla.test:8080/dashboard/setup.json &> /dev/stdout

cat /tmp/error.log
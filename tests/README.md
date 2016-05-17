# Running Vanilla's Tests

Vanilla's tests are designed to work on [Travis CI](https://travis-ci.org/), but you can set your local environment up
to run the tests with minimal setup. Here is what you need.

1. Your localhost MySQL server will need a user named "travis" with a blank password. This user will need to be able to
create and drop databases. The only database that the tests used is called "vanilla_test".

2. The copy of Vanilla you have installed, must respond to http://vanilla.test:8080. You can use the NGINX template in
tests/travis/nginx/default-site.tpl.conf as a guideline. If you are on Apache then the default .htaccess file should
work for you.

3. Remember to install all of the developer dependencies with `composer install`.
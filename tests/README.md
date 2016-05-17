# Vanilla's Tests

Vanilla's tests are designed to work on [Travis CI](https://travis-ci.org/), but you can set your local environment up
to run the tests with minimal setup.

Vanilla has 2 testsuites, "Library" and "APIv0". The requirements below are for the "APIv0" testsuite, which does our integration testing via web server. The "Library" testsuite is actual unit testing. For more thorough results, you must run both.

You can use the `--testsuite Library` flag when running phpunit to only run our unit tests and bypass the web server requirements below.

## Requirements

1. Your localhost MySQL server must have a user named `travis` with a blank password and permission to
create and drop databases. The only database that the tests use is `vanilla_test`.

2. Your copy of Vanilla must respond to `http://vanilla.test:8080`. 
  * You can use the nginx template in
`tests/travis/nginx/default-site.tpl.conf` as a guideline. 
  * Pay particular attention to the `/cgi-bin` mapping.    
  * If you are on Apache, the default `.htaccess` file should work for you.

3. All of the developer dependencies are installed with `composer install`.

4. PHPUnit must be [installed](https://github.com/sebastianbergmann/phpunit#installation).

## Running

1. Go to your Vanilla install directory.
2. `phpunit -c phpunit.xml.dist`
# Vanilla's Tests

Vanilla's tests are designed to work on [Travis CI](https://travis-ci.org/), but you can set your local environment up
to run the tests with minimal setup. 

## Requirements

1. Your localhost MySQL server must have a user named `travis` with a blank password and permission to
create and drop databases. The only database that the tests use is `vanilla_test`.

2. Your copy of Vanilla must respond to `http://vanilla.test:8080`. You can use the nginx template in
`tests/travis/nginx/default-site.tpl.conf` as a guideline. If you are on Apache, the default `.htaccess` file should work for you.

3. All of the developer dependencies installed with `composer install`.

4. [PHPUnit installed](https://github.com/sebastianbergmann/phpunit#installation).

## Running

1. Go to your Vanilla install directory.
2. `phpunit -c phpunit.xml.dist --testsuite Library`

To run without the `--testsuite` flag requires that `/cgi-bin` be mapped, as in the template mentioned above. In nginx, try:

```
    location ~* "^/cgi-bin/.+\.php(/|$)" {
        root /var/www;
        set $downstream_handler php;
        # send to fastcgi
        include fastcgi.conf;
        fastcgi_pass php-fpm;
    }
```
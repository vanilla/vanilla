# Vanilla's Tests

Vanilla's tests are designed to work on [Travis CI](https://travis-ci.org/), 
but you can set your local environment up to run the tests with minimal setup.

Vanilla has tests for both PHP and Javascript (typescript).

## PHP

All of the PHP tests are located in this `tests` directory.

Vanilla has PHP 3 testsuites, `Library` and `APIv0`, `APIv2`.

The requirements below are for the `APIv0` and `APIv2` testsuite, which does our integration testing via web server. 
The `Library` testsuite is actual unit testing. For more thorough results, you must run all 3.

### `Library` requirements

1. PHPUnit must be [installed](https://github.com/sebastianbergmann/phpunit#installation).

1. All of the developer dependencies are installed with `composer install`.

### `APIv0` & `APIv2` Requirements

1. PHPUnit must be [installed](https://github.com/sebastianbergmann/phpunit#installation).

1. All of the developer dependencies are installed with `composer install`.

1. Your localhost MySQL server must have a user named `travis` with a blank password and permission to
create and drop databases. The only database that the tests use is `vanilla_test`.

1. Your copy of Vanilla must respond to `http://vanilla.test:8080`.
  * You can use the nginx template in `tests/travis/templates/nginx/sites-enabled/default-site.tpl.conf` as a guideline. 
  * Pay particular attention to the `/cgi-bin` mapping
  * If you are on Apache, the default `.htaccess` file should work for you.

1. You must put `tests/travis/templates/vanilla/conf/bootstrap.before.php` in your `conf/` folder.
  * This will ensure that the unit tests use their own config and cache path.

### Running

1. Go to your Vanilla install directory.
1. `phpunit -c phpunit.xml.dist`

It is possible to run only a single test suite by using the flag `--testsuite <SUITE_NAME>`.

For example `phpunit -c phpunit.xml.dist --testsuite Library` would run only our unit tests 
and bypass the web server requirements below.

## Javascript

Vanilla's JS tests are written in typescript and live directly next to the source files that they test.
Any file matching the following pattern is considered a unit test and will be run:

- `applications/*/src/scripts/**/*.(ts|tsx)`
- `plugins/*/src/scripts/**/*.(ts|tsx)`

### Requirements

In order to run the tests node_modules must be installed in root directory of vanilla.

```sh
# Modules are installed with --pure-lockfile for 100% consistent builds.
cd </PATH/TO/VANILLA>
yarn install --pure-lockfile
```

If you have additional plugins symlinked into vanilla that have their own tests (such as [rich-editor](https://github.com/vanilla/rich-editor)), then you will also need to install the node_modules for that plugin.

### Running the tests.

1. Make sure you are in the root of your vanilla installation.
1. Run `yarn test`.

Tests can also be run in watch mode by running `yarn test:watch`.


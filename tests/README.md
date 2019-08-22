# Vanilla's Tests

Vanilla's tests are designed to work on [CircleCI](https://circleci.com/gh/vanilla/vanilla),
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

1. Your localhost MySQL server must have a user named `circleci` with a blank password and permission to
create and drop databases. The only database that the tests use is `vanilla_test`.

1. Your copy of Vanilla must respond to `http://vanilla.test:8080`.
    - You can use the nginx template in `.circleci/scripts/templates/nginx/sites-enabled/default-site.tpl.conf` as a guideline.
    - Pay particular attention to the `/cgi-bin` mapping
    - If you are on Apache, the default `.htaccess` file should work for you.

1. You must put `.circleci/scripts/templates/vanilla/conf/bootstrap.before.php` in your `conf/` folder.
    - This will ensure that the unit tests use their own config and cache path.

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

### Running the tests

1. Make sure you are in the root of your vanilla installation.
1. Run `yarn test`.

Tests can also be run in watch mode by running `yarn test:watch`.

### Debugging tests

By running the the `yarn test:debug` command, the test suite will start up in a normal (not headless) version of Chrome.
The debugging port will be set to `9333`. If your IDE supports attaching to chrome on a debugging port,
then you can attach after the browser has started, set breakpoints, re-run the tests, and step through the code.

#### Debugging in VSCode

Below is a VSCode [launch.json file](https://code.visualstudio.com/docs/editor/debugging) that will attach to our test browser if it is running.

The `sourceMapPathOverrides` is optional, but is necessary if you wish to debug a symlinked file or a file in a symlinked directory. This example, provide the default webpack mapping (you need to provide because this completely overrides the default), as well as an additional mapping for a symlinked plugin. This path may vary depending ok the plugin and your development environment.

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "type": "chrome",
            "request": "attach",
            "name": "Attach Karma Chrome",
            "address": "localhost",
            "port": 9333,
            "webRoot": "${workspaceFolder}",
            "pathMapping": {
                "/": "${workspaceRoot}",
                "/base/": "${workspaceRoot}/"
            },
            "sourceMapPathOverrides": {
                "webpack:///./*": "${webRoot}/*",
                "webpack:///../rich-editor/plugins/rich-editor/*": "${webRoot}/plugins/rich-editor/*"
            }
        }
    ]
}
```

#### In Browser Debugging

If your IDE does not support attaching in this manner,
you can still debug using the [Chrome Dev Tools](https://developers.google.com/web/tools/chrome-devtools/)
provided in the test browser.

To debug directory in the browser:

1. Click the `Debug` button, located in the top right hand corner of the test browser.
1. Open the [Chrome Dev Tools](https://developers.google.com/web/tools/chrome-devtools/). This can be done by right clicking the
 contents of the page and clicking `inspect` or by navigating the menu `View -> Developer -> View -> Developer Tools`.
1. Navigate to the `Sources` tab in the developer tools.
1. Locate the file you wish to debug in the file tree in the left panel.
1. Place a breakpoint.
1. Refresh the page.

**Note About the File Tree**
The file tree is generated from source maps and can be found under the `webpack://` "directory". Of the subdirectrories below the primary one you likely care about is `.` which represents the vanilla root. If you are debugging an addon that is symlinked into vanilla, it will not appear under the vanilla root. Instead it will appear as a relative path to the root of your installation based on its actual resolved path. This will likely look something like this: `../my-addon-repo/plugins/my-plugin/src/scripts` or possibly even a longer relative path starting with `../../` or `../../../` depending on your setup.

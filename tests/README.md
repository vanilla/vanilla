
#Testing Vanilla

Vanilla testing is powered by [Codeception](http://codecepton.com).

Continuous integration testing run by [travis-ci.org](http://travis-ci.org).

Testing results can be seen at [https://travis-ci.org/vanilla/vanilla](http://travis-ci.org/vanilla/vanilla)  

##Quick Start

###Install codeception with composer global.

```
$ cd ~
$ composer global require 'codeception/codeception=*'
```


###Configure your localhost for testing

####Configure webserver hostname

Edit /etc/hosts and add the following line:

On windows this file is %systemroot%\system32\drivers\etc\hosts

```
127.0.0.1 codeception.local 
```

####Configure webserver  

Apache

```
<VirtualHost *:80>
    ServerAdmin webmaster@dummy-host.example.com
    DocumentRoot "/Users/johnashton/development/codeception/vanilla"
    ServerName codeception.local 
</VirtualHost>

```

####Create directory for codeception
This directory should be different then your local testing environment.
Be sure that you keep it updated if you are running tests on localhost.

```
$ cd ~/development/
$ mkdir codeception
$ cd codeception
$ git clone git@github.com:vanilla/vanilla.git
```

####Create Mysql database and user.

```
mysql -uroot -proot -e "CREATE DATABASE codeception_vanilla;"
mysql -uroot -proot -e 
"GRANT ALL PRIVILEGES ON codeception_vanilla.* TO codeception@localhost IDENTIFIED BY 'codeception'"
```

####Install and Start PhantomJS

http://phantomjs.org/download.html

```
brew update && brew install phantomjs
```

Start PhantomJS.  Suggested to run this in a screen.
```
$ phantomjs --webdriver=4444 &
```

####Create a local bootstrap file (optional).  

If you don't need to change the default settings, then there is no need to complete this step.


```
$ hostname
Johns-MacBook-Pro.local
```

```
$ cd vanilla
$ cp tests/_bootstrap.skeleton.php tests/_bootstrap.Johns-MacBook-Pro.local.php
```

Now edit your local bootstrap file.

```
define('VANILLA_APP_TITLE', 'Codeception');
define('VANILLA_ADMIN_EMAIL', 'codeception@vanillaforums.com');
define('VANILLA_ADMIN_USER', 'admin');
define('VANILLA_ADMIN_PASSWORD', 'admin');

define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'root');
define('MYSQL_PASSWORD', '');
define('MYSQL_DATABASE', 'codeception_vanilla');
```

####Build the Testers

```
$ cd vanilla
$ codecept build
Building Actor classes for suites: acceptance, functional, unit
AcceptanceTester includes modules: AcceptanceHelper, WebDriver, Asserts
AcceptanceTester.php generated successfully. 90 methods added
FunctionalTester includes modules: Filesystem, FunctionalHelper, PhpBrowser, Db, Asserts
FunctionalTester.php generated successfully. 81 methods added
UnitTester includes modules: Asserts, UnitHelper, Db
UnitTester.php generated successfully. 21 methods added
```


####Run the test suite.

```
$ codecept run
Codeception PHP Testing Framework v2.0.5
Powered by PHPUnit 4.0.16 by Sebastian Bergmann.
Checking for local bootstrap file: /Users/johnashton/development/vanilla/tests/_bootstrap.Johns-MacBook-Pro.local.php
Loading local bootstrap file

Acceptance Tests (4) -------------------------------------------------------------------------------------------------------------
Trying to check or setup vanilla (AcceptanceSetup::CheckOrSetupVanilla)                                                      Ok

...

Functional Tests (3) ------------------------------------------------------------------------------------------------------------
Trying to check or setup vanilla (FunctionalSetup::CheckOrSetupVanilla)                                                     Ok

...

Unit Tests (6) --------------------------------------------------------------------------------------------------------------------
Trying to test database (DatabaseTest::testDatabase)                                                                          Ok

...

Time: 36.24 seconds, Memory: 19.75Mb

OK (31 tests, 45 assertions)

$                                                   


```

Now you can start creating your own tests.


## Coverage Reports

```
$ codecept run unit --coverage-html
```

Test are generated in tests/_output.
To view the coverage reports go to [http://codeception.local/tests/_output/coverage/](http://codeception.local/tests/_output/coverage/)



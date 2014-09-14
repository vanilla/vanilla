<?php
// This is global bootstrap for autoloading 

$localBootstrapFile = __DIR__ . '/_bootstrap.'.gethostname() . '.php';
// echo 'Checking for local bootstrap file: ' . $localBootstrapFile . PHP_EOL;
if (file_exists($localBootstrapFile)) {

    echo 'Loading local bootstrap file' . PHP_EOL;
    include $localBootstrapFile;

} else {

//    echo 'File not found.  Using global bootstrap.' . PHP_EOL;

    define('VANILLA_APP_TITLE', 'Codeception');
    define('VANILLA_ADMIN_EMAIL', 'codeception@vanillaforums.com');
    define('VANILLA_ADMIN_USER', 'admin');
    define('VANILLA_ADMIN_PASSWORD', 'admin');

    define('MYSQL_HOST', 'localhost');
    define('MYSQL_USER', 'root');
    define('MYSQL_PASSWORD', '');
    define('MYSQL_DATABASE', 'codeception_vanilla');

}

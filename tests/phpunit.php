<?php

// Define some constants to help with testing.
define('APPLICATION', 'Vanilla Tests');
define('PATH_ROOT', realpath(__DIR__.'/..'));

// Autoload all of the classes.
require PATH_ROOT.'/vendor/autoload.php';

// Copy the cgi-bin files.
$dir = PATH_ROOT.'/cgi-bin';
if (!file_exists($dir)) {
    mkdir($dir);
}

$files = glob(__DIR__."/travis/cgi-bin/*.php");
foreach ($files as $file) {
    $dest = $dir.'/'.basename($file);
    $r = copy($file, $dest);
}

<?php
$path = parse_url($_SERVER["REQUEST_URI"])["path"];

if (preg_match("/dist/", $path) || file_exists("./" . $path)) {
    return false;
} else {
    // Kludge because PATH_INFO isn't set
    // https://bugs.php.net/bug.php?id=61286
    $_SERVER["PATH_INFO"] = $path;

    include_once __DIR__ . "/../index.php";
    return true;
}

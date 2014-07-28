<?php
define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.2.15.1');

// Here you can initialize variables that will be available to your tests
define('PATH_ROOT', realpath(__DIR__ . '/../../'));
if (!defined('PATH_CONF')) {
    define('PATH_CONF', PATH_ROOT . '/conf');
}

// Include default constants if none were defined elsewhere
if (!defined('VANILLA_CONSTANTS')) {
    include(PATH_CONF . '/constants.php');
}

require_once(PATH_LIBRARY_CORE . '/functions.error.php');
require_once(PATH_LIBRARY_CORE . '/functions.general.php');
require_once(PATH_LIBRARY_CORE . '/functions.compatibility.php');


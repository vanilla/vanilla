<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

define("APPLICATION_VERSION", "tests");

require_once __DIR__ . "/definitions.php";

// Alias classes for some limited PHPUnit v5 compatibility with v6.
$classCompatibility = [
    "PHPUnit\\Framework\\TestCase" => "PHPUnit_Framework_TestCase", // See https://github.com/php-fig/log/pull/52
];
foreach ($classCompatibility as $class => $legacyClass) {
    if (!class_exists($legacyClass) && class_exists($class)) {
        class_alias($class, $legacyClass);
    }
}

// ===========================================================================
// Adding the minimum dependencies to support unit testing for core libraries
// ===========================================================================
require PATH_ROOT . "/environment.php";

// Allow any addon class to be auto-loaded.
\VanillaTests\Bootstrap::registerAutoloader();

require_once PATH_LIBRARY_CORE . "/functions.validation.php";

require_once PATH_LIBRARY_CORE . "/functions.render.php";

// Load this for psalm
require_once PATH_PLUGINS . "/Reactions/views/reaction_functions.php";
require_once PATH_PLUGINS . "/Reactions/views/settings_functions.php";
require_once PATH_PLUGINS . "/ProfileExtender/views/helper_functions.php";
require_once PATH_APPLICATIONS . "/dashboard/views/profile/connection_functions.php";
require_once PATH_APPLICATIONS . "/dashboard/views/profile/helper_functions.php";
require_once PATH_APPLICATIONS . "/dashboard/views/settings/helper_functions.php";
require_once PATH_APPLICATIONS . "/vanilla/views/categories/helper_functions.php";
require_once PATH_APPLICATIONS . "/vanilla/views/discussions/helper_functions.php";
require_once PATH_APPLICATIONS . "/vanilla/views/discussion/helper_functions.php";

// Include test utilities.
$utilityFiles = array_merge(
    glob(PATH_ROOT . "/plugins/*/tests/Utils/*.php"),
    glob(PATH_ROOT . "/applications/*/tests/Utils/*.php")
);
foreach ($utilityFiles as $file) {
    require_once $file;
}

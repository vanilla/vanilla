<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);
ini_set('display_errors', 0);
ini_set('track_errors', 1);

ob_start();
$newThemeKey = $argv[1] ?? "null";
if ($newThemeKey === null) {
    die("Did not pass a theme key");
}

include_once(__DIR__ . "/../../environment.php");
include_once(__DIR__ . "/../../bootstrap.php");

$config = new Gdn_Configuration();
// Load installation-specific configuration so that we know what apps are enabled.
$config->load($config->defaultPath(), 'Configuration', true);

$config->set('Garden.Theme', $newThemeKey);
$config->set('Garden.MobileTheme', $newThemeKey);
$config->set('Garden.CurrentTheme', $newThemeKey);

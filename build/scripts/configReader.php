<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

$configFile = array_key_exists(1, $argv) ? $argv[1] : "config.php";
$configPath = realpath(__DIR__."/../../conf/".$configFile);
$defaultConfigPath = realpath(__DIR__."/../../conf/config-defaults.php");
define("APPLICATION", "VANILLA_BUILD");
define("PATH_CACHE", null);

include $defaultConfigPath;
include $configPath;

$Configuration = isset($Configuration) ? $Configuration : [];
echo json_encode($Configuration);

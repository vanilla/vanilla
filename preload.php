<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\AddonManager;

define("PATH_ROOT", realpath(__DIR__));
require PATH_ROOT . "/environment.php";

// Loadup library
$dirs = ["/library"];
foreach ($dirs as $dir) {
    $directory = new RecursiveDirectoryIterator(PATH_ROOT . $dir);
    $fullTree = new RecursiveIteratorIterator($directory);
    $phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RecursiveRegexIterator::GET_MATCH);

    foreach ($phpFiles as $key => $file) {
        if (
            // Make sure we don't load any files that declare global functions or have side effects.
            str_contains($file[0], "/views/") ||
            str_contains($file[0], "/settings/") ||
            str_contains($file[0], "functions.") ||
            str_contains($file[0], "bootstrap") ||
            // Smarty is not compatible with preload because it autoloads pretty weirdly.
            // Essentially it's main class defines a bunch of constants that never get recreated if the classes were preloaded.
            str_contains(strtolower($file[0]), "smarty")
        ) {
            continue;
        }
        require_once $file[0];
    }
}

// Loadup addons.
$addonManager = new AddonManager(AddonManager::getDefaultScanDirectories(), PATH_CACHE);
$addonManager->preloadAddonClasses([
    // It's very important that none of the classes here declare any global functions inside of their class declarations.
    // If that is the case the function will be loaded permanently and take precedence at runtime, even if the addon is not enabled.
    //
    // If you are going to add more, validate the following.
    // - Class declarations in that plugin do not have sideeffects outside of the class declaration.
    // - Nothing is doing class_exists() checks to determine if the plugin is loaded.
    "dashboard",
    "vanilla",
    "conversations",
    "groups",
    "knowledge",
    "vanillaanalytics",
    "Reactions",
    "ideation",
    "elasticsearch",
    "themingapi",
    "badges",
    "QnA",
]);

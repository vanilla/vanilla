<?php
/**
 * This file will make Vanilla use a different config depending on which site you're on.
 */

$host = $_SERVER['HTTP_HOST'];
list($host, $_) = explode(':', $host, 2);

// Get the config.
if (isset($_SERVER['NODE_SLUG'])) {
   // This is a site per folder setup.
   $slug = "$host-{$_SERVER['NODE_SLUG']}";
} else {
   // This is a site per host setup.
   if (in_array($host, ['vanilla.local', 'vanilla.lc'])) {
      $slug = 'config';
   } else {
      $slug = $host;
   }
}

// Use a config specific to the site.
$configPath = PATH_ROOT."/conf/$slug.php";

define('PATH_CONF_DEFAULT', $configPath);

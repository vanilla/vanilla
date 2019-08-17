<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * Setup Unit tests config and cache paths
 */

call_user_func(function () {
    $host = explode(':', $_SERVER['HTTP_HOST'], 2)[0];
    // This is a site per host setup.
    if ($host === 'vanilla.test') {
        define('PATH_CONF_DEFAULT', PATH_ROOT."/conf/$host.php");
        define('PATH_CACHE', PATH_ROOT."/cache/$host/");
    }
});

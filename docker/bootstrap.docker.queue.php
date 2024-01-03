<?php

require_once PATH_ROOT . "/docker/bootstrap.docker.php";

$config = \Gdn::config();

$config->touch([
    "Vanilla.AccountID" => 1,
    "Vanilla.SiteID" => random_int(1, 9999999999),
    "VanillaQueue.Keys.Private" => bin2hex(random_bytes(32)),
]);

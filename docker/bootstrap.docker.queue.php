<?php

use Vanilla\Site\OwnSite;
use Vanilla\VanillaQueue\Driver\VanillaQueueClient;

require_once PATH_ROOT . "/docker/bootstrap.docker.php";

$config = \Gdn::config();

$privateKeyConfig = "VanillaQueue.Keys.Private";
if (!$config->get($privateKeyConfig)) {
    $privateKey = bin2hex(random_bytes(32));
    $config->saveToConfig([$privateKeyConfig => $privateKey]);
}

if ($config->get(OwnSite::CONF_SITE_ID, null) === null) {
    $config->saveToConfig([OwnSite::CONF_SITE_ID => random_int(1, 9999999999)]);
}

$config->saveToConfig(
    [
        "EnabledPlugins.vanilla-queue" => true,
        "VanillaQueue.BaseUrl" => "http://queue.vanilla.localhost",
    ],
    null,
    false
);

<?php

require_once PATH_ROOT . "/docker/bootstrap.docker.php";

$config = \Gdn::config();

$confQueuePrivateKey = "VanillaQueue.Keys.Private";
if (!$config->get($confQueuePrivateKey)) {
    $privateKey = bin2hex(random_bytes(32));
    $config->saveToConfig([$confQueuePrivateKey => $privateKey]);
}

$confSiteID = "Vanilla.SiteID";

if ($config->get($confSiteID, null) === null) {
    $config->saveToConfig([$confSiteID => random_int(1, 9999999999)]);
}

$config->saveToConfig(
    [
        "EnabledPlugins.vanilla-queue" => true,
        "VanillaQueue.BaseUrl" => "http://queue.vanilla.localhost",
        "JobQueue.Threshold" => 0,
    ],
    null,
    false
);

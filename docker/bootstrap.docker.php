<?php

if (!str_contains(php_sapi_name(), "fpm")) {
    // Bailout for minimal sapi tests.
    return;
}
$config = \Gdn::config();

$config->saveToConfig(
    [
        "Cache.Enabled" => true,
        "Cache.Method" => "memcached",
        "Cache.Memcached.Store" => "memcached:11211",
        "Cache.Memcached.Option" => [
            Memcached::OPT_COMPRESSION => true,
            Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
            Memcached::OPT_NO_BLOCK => true,
            Memcached::OPT_TCP_NODELAY => true,
            Memcached::OPT_CONNECT_TIMEOUT => 1000,
            Memcached::OPT_SERVER_FAILURE_LIMIT => 2,
        ],
    ],
    null,
    false // These configs are only in memory.
);

$config->touch([
    "Vanilla.AccountID" => 1,
    "Vanilla.SiteID" => random_int(1, 9999999999),
    "VanillaQueue.Keys.Private" => bin2hex(random_bytes(32)),
]);

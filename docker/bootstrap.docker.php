<?php

if (php_sapi_name()) {
    // Bailout for minimal sapi tests.
    return;
}
$config = \Gdn::config();

$config->saveToConfig(
    [
        "EnabledPlugins.syslogger" => true,
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
        "Garden.Email.Format" => "html",
        "Garden.Email.Hostname" => "localmail",
        "Garden.Email.SmtpHost" => "localmail",
        "Garden.Email.SmtpPort" => 1025,
        "Garden.Email.UseSmtp" => true,
    ],
    null,
    false // These configs are only in memory.
);

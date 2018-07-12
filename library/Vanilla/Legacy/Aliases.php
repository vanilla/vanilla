<?php

namespace Vanilla\Legacy;

final class Aliases {
    const ALIASES = [
        'Gdn_Request' => \Vanilla\Web\Request::class,
        'Gdn_PluginManager' => PluginManager::class,
    ];

    public static function autoload($className) {
        if (isset(self::ALIASES[$className])) {
            $orig = self::ALIASES[$className];
            trigger_error("$className is deprecated. Use $orig instead", E_USER_DEPRECATED);
            class_alias($orig, $className, true);
        }
    }
}

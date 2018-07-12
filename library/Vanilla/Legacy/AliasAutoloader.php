<?php

namespace Vanilla\Legacy;

final class AliasAutoloader {
    const ALIASES = [
        'BBCode' => \Vanilla\Formatting\BBCodeFormatter::class,
        'Emoji' => \Vanilla\Formatting\EmojiInterpreter::class,
        'Gdn_Format' => \Vanilla\Formatting\FormatUtility::class,
        'Gdn_Pluggable' => \Vanilla\Pluggable::class,
        'VanillaHtmlFormatter' => \Vanilla\Formatting\HTMLFormatter::class,
        'MarkdownVanilla' => \Vanilla\Formatting\MarkdownFormatter::class,
    ];

    public static function autoload($className) {
        if (isset(self::ALIASES[$className])) {
            $orig = self::ALIASES[$className];
            trigger_error("$className is deprecated. Use $orig instead", E_USER_DEPRECATED);
            class_alias($orig, $className, true);
        }
    }
}

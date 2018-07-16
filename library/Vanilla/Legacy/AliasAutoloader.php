<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Legacy;

/**
 * An autoloader for class aliases.
 *
 * We need this because declaring a class_alias autoloads the class.
 * Declaring aliases up front (like in the bootstrap) would autoload all of our classes.
 * This class provides an autoloader for usage with spl_autoload_register to autoload these aliases
 * which will then autoload their new classes if they are not loaded yet.
 */
final class AliasAutoloader {
    /**
     * An array of OLD_CLASS_NAME => New classname.
     */
    const ALIASES = [
        'BBCode' => \Vanilla\Formatting\BBCodeFormatter::class,
        'Emoji' => \Vanilla\Formatting\EmojiInterpreter::class,
        'Gdn_Format' => \Vanilla\Formatting\FormatUtility::class,
        'Gdn_Pluggable' => \Vanilla\Pluggable::class,
        'VanillaHtmlFormatter' => \Vanilla\Formatting\HTMLFormatter::class,
        'MarkdownVanilla' => \Vanilla\Formatting\MarkdownFormatter::class,
    ];

    /**
     * An autoload function for use with spl_autoload_register.
     *
     * @param string $className the class name to try and load.
     */
    public static function autoload($className) {
        if (isset(self::ALIASES[$className])) {
            $orig = self::ALIASES[$className];
            trigger_error("$className is deprecated. Use $orig instead", E_USER_DEPRECATED);
            class_alias($orig, $className, true);
        }
    }
}

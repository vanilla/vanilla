<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Utilities related to HMTL formatting.
 */
class HtmlUtils {

    /**
     * Join some group of CSS classnames.
     *
     * @param mixed[] $args Multiple CSS classnames to join together. Items may be string or null.
     *
     * @return string The CSS classes joined together.
     */
    public static function classNames(...$args): string {
        $args = array_filter($args, function ($item) {
            return is_string($item);
        });
        return implode(' ', $args);
    }
}

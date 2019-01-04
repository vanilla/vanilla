<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Translates a string.
 *
 * @see t()
 */
function smarty_modifier_translate($code, $default = false) {
    if ($default === false) {
        $default = $code;
    }

	return t($code, $default);
}

<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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

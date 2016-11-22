<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Translates a string.
 *
 * @see t()
 */
function smarty_modifier_translate($Code, $Default = false) {
    if ($Default === false) {
        $Default = $Code;
    }

	return t($Code, $Default);
}

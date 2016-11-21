<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Formats a date.
 *
 * @see Gdn_Format::Date()
 */
function smarty_modifier_date($Date, $Format = '') {
	return Gdn_Format::date($Date, $Format);
}

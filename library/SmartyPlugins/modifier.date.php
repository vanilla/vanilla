<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Formats a date.
 *
 * @see Gdn_Format::date()
 */
function smarty_modifier_date($date, $format = '') {
	return Gdn_Format::date($date, $format);
}

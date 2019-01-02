<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

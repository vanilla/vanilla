<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Checks config to see if we're using the data driven header
 * @return boolean The config
 */
function smarty_function_has_data_driven_header($params, &$smarty) {
	return Gdn::config("Feature.DataDrivenHeader.Enabled", false);
}

<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_module($params, &$smarty) {
    $name = val('name', $params);
    unset($params['name']);
   
    $result = Gdn_Theme::module($name, $params);
	return $result;
}

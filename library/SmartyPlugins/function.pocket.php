<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes a pocket to the page
 *
 * @param array $params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the pocket.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string The pocket string.
 */
function smarty_function_pocket($params, $smarty) {
    if (!class_exists('PocketsPlugin')) {
        return '';
    }

    $name = val('name', $params);
    unset($params['name']);

    $result = PocketsPlugin::pocketString($name, $params);

	return $result;
}

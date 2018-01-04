<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes the site logo to the page.
 *
 * @param array $params The parameters passed into the function. This currently takes no parameters.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string The url.
 */
function smarty_function_vanillaurl($params, &$smarty) {
    return c('Garden.VanillaUrl');
}

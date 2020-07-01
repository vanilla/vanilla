<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Returns the  custom text from a theme.
 *
 * @param array $params The parameters passed into the function. This currently takes no parameters.
 *  - <b>code</b>: The text code set in the theme's information.
 *  - <b>default</b>: The default text if the user hasn't overridden.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string The text.
 */
function smarty_function_text($params, &$smarty) {
    $result = Gdn_Theme::text(val('code', $params, ''), val('default', $params, ''));
    return $result;
}

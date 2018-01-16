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
 * @param array $params The parameters passed into the function.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return The HTML img tag or site title if no logo is set.
 */
function smarty_function_logo($params, &$smarty) {
    $options = [];

    // Whitelist params to be passed on.
    if (isset($params['alt'])) {
        $options['alt'] = $params['alt'];
    }
    if (isset($params['class'])) {
        $options['class']  = $params['class'];
    }
    if (isset($params['title'])) {
        $options['title']  = $params['title'];
    }
    if (isset($params['height'])) {
        $options['height'] = $params['height'];
    }
    if (isset($params['width'])) {
        $options['width']  = $params['width'];
    }

    $result = Gdn_Theme::logo($options);
	return $result;
}

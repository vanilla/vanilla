<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
function smarty_function_mobile_logo($params, &$smarty) {
    $options = [];
    if (isset($params['fallbackLogo'])) {
        $options['fallbackLogo']  = $params['fallbackLogo'];
    }
    $result = Gdn_Theme::mobileLogo($params);
	return $result;
}

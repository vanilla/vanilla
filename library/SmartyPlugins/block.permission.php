<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $params The parameters passed into the function.
 * @param string $content
 * @param object $smarty The smarty object rendering the template.
 * @param bool $repeat
 * @return string The url.
 */
function smarty_block_permission($params, $content, &$smarty, &$repeat) {
    // Only output on the closing tag.
    if (!$repeat){
        if (isset($content)) {
            $require = val('require', $params);
            $hasPermission = Gdn::session()->checkPermission($require);
            if ($hasPermission) {
                return $content;

            }
        }
    }
}

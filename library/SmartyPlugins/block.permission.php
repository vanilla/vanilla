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

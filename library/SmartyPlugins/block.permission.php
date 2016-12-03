<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $Params The parameters passed into the function.
 * @param string $Content
 * @param object $Smarty The smarty object rendering the template.
 * @param bool $Repeat
 * @return string The url.
 */
function smarty_block_permission($Params, $Content, &$Smarty, &$Repeat) {
    // Only output on the closing tag.
    if (!$Repeat){
        if (isset($Content)) {
            $Require = val('require', $Params);
            $HasPermission = Gdn::session()->checkPermission($Require);
            if ($HasPermission) {
                return $Content;

            }
        }
    }
}

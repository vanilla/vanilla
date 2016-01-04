<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * A placeholder for future menu items.
 *
 * @param array $Params The parameters passed into the function.
 * @param Smarty $Smarty The smarty object rendering the template.
 * @return string
 */
function smarty_function_custom_menu($Params, &$Smarty) {
    $Controller = $Smarty->Controller;
    if (is_object($Menu = val('Menu', $Controller))) {
        $Format = val('format', $Params, wrap('<a href="%url" class="%class">%text</a>', val('wrap', $Params, 'li')));
        $Result = '';
        foreach ($Menu->Items as $Group) {
            foreach ($Group as $Item) {
                // Make sure the item is a custom item.
                if (valr('Attributes.Standard', $Item)) {
                    continue;
                }

                // Make sure the user has permission for the item.
                if ($Permission = val('Permission', $Item)) {
                    if (!Gdn::session()->checkPermission($Permission)) {
                        continue;
                    }
                }

                if (($Url = val('Url', $Item)) && ($Text = val('Text', $Item))) {
                    $Attributes = val('Attributes', $Item);
                    $Result .= Gdn_Theme::link($Url, $Text, $Format, $Attributes)."\r\n";
                }
            }
        }
        return $Result;
    }
    return '';
}

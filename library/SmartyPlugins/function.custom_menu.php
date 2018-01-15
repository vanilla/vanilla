<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * A placeholder for future menu items.
 *
 * @param array $params The parameters passed into the function.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string
 */
function smarty_function_custom_menu($params, &$smarty) {
    $controller = Gdn::controller();
    if (is_object($menu = val('Menu', $controller))) {
        $format = val('format', $params, wrap('<a href="%url" class="%class">%text</a>', val('wrap', $params, 'li')));
        $result = '';
        foreach ($menu->Items as $group) {
            foreach ($group as $item) {
                // Make sure the item is a custom item.
                if (valr('Attributes.Standard', $item)) {
                    continue;
                }

                // Make sure the user has permission for the item.
                if ($permission = val('Permission', $item)) {
                    if (!Gdn::session()->checkPermission($permission)) {
                        continue;
                    }
                }

                if (($url = val('Url', $item)) && ($text = val('Text', $item))) {
                    $attributes = val('Attributes', $item);
                    $result .= Gdn_Theme::link($url, $text, $format, $attributes)."\r\n";
                }
            }
        }
        return $result;
    }
    return '';
}

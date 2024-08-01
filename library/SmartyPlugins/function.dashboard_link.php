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
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_dashboard_link($params, &$smarty)
{
    $path = val("path", $params, "", true);
    $text = val("text", $params, "", true);
    $wrap = val("wrap", $params, "li");
    return Gdn_Theme::link(
        "dashboard",
        val("text", $params, ""),
        val("format", $params, wrap('<a href="%url" class="%class">%text</a>', $wrap))
    );
}

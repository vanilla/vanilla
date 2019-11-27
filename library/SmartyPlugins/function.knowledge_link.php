<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Adds link to KB
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_knowledge_link($params, &$smarty) {
    if (gdn::session()->checkPermission("knowledge.kb.view")) {
        $wrap = val('wrap', $params, 'li');
        return Gdn_Theme::link('kb', val('text', $params, t('Help Menu', "Help")), val('format', $params, wrap('<a href="%url" class="%class">%text</a>', $wrap)));
    } else {
        return "";
    }
}

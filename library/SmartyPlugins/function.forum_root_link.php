<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Takes a route and prepends the web root (expects "/controller/action/params" as $Path).
 *
 * @param array $params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>text</b>: Html text to be put inside an anchor. If this value is set then an html <a></a> is returned rather than just a url.
 * - <b>id, class, etc.></b>: When an anchor is generated then any other attributes are passed through and will be written in the resulting tag.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string Returns the url.
 */
function smarty_function_forum_root_link($params, &$smarty) {
    $text = val('text', $params, '');
    $format = val('format', $params, '<li><a href="%url" class="%class">%text</a></li>');

    $options = [];
    if (isset($params['class'])) {
        $options['class'] = $params['class'];
    }

    $result = Gdn_Theme::link('forumroot', $text, $format, $options);
    return $result;
}

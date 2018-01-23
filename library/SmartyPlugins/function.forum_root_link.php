<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Takes a route and prepends the web root (expects "/controller/action/params" as $Path).
 *
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>path</b>: The relative path for the url. There are some special paths that can be used to return "intelligent" links:
 *    - <b>signinout</b>: This will return a signin/signout url that will toggle depending on whether or not the user is already signed in. When this path is given the text is automaticall set.
 * - <b>withdomain</b>: Whether or not to add the domain to the url.
 * - <b>text</b>: Html text to be put inside an anchor. If this value is set then an html <a></a> is returned rather than just a url.
 * - <b>id, class, etc.></b>: When an anchor is generated then any other attributes are passed through and will be written in the resulting tag.
 * @param Smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_forum_root_link($params, &$smarty) {
    $text = val('text', $params, '', true);
    $format = val('format', $params, '<li><a href="%url" class="%class">%text</a></li>');

    $options = [];
    if (isset($params['class'])) {
        $options['class'] = $params['class'];
    }

    $result = Gdn_Theme::link('forumroot', $text, $format, $options);
    return $result;
}

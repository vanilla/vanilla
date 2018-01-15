<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Includes a file in template. Handy for adding html files to tpl files
 *
 * @param array $params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the file.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string The rendered asset.
 */
function smarty_function_include_file($params, &$smarty) {
    $name = ltrim(val('name', $params), '/');
    if (strpos($name, '..') !== false) {
        return '<!-- Error, moving up directory path not allowed -->';
    }
    if (isUrl($name)) {
        return '<!-- Error, urls are not allowed -->';
    }
    $filename = rtrim($smarty->getTemplateDir(), '/').'/'.$name;
    if (!file_exists($filename)) {
        return '<!-- Error, file does not exist -->';
    }
    return file_get_contents($filename);
}

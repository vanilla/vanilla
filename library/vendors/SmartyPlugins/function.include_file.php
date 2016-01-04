<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Includes a file in template. Handy for adding html files to tpl files
 *
 * @param array $Params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the file.
 * @param Smarty $Smarty The smarty object rendering the template.
 * @return string The rendered asset.
 */
function smarty_function_include_file($Params, &$Smarty) {
    $Name = ltrim(val('name', $Params), '/');
    if (strpos($Name, '..') !== false) {
        return '<!-- Error, moving up directory path not allowed -->';
    }
    if (isUrl($Name)) {
        return '<!-- Error, urls are not allowed -->';
    }
    $filename = rtrim($Smarty->template_dir, '/').'/'.$Name;
    if (!file_exists($filename)) {
        return '<!-- Error, file does not exist -->';
    }
    return file_get_contents($filename);
}

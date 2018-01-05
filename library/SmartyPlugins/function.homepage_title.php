<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 *
 * Returns Homepage Title from config
 *
 * @return string
 */
function smarty_function_homepage_title() {
    return c('Garden.HomepageTitle', '');
}

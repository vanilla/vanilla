<?php
/**
 * Returns Homepage Title from config
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 * @return string
 */
function smarty_function_homepage_title() {
    return c('Garden.HomepageTitle', '');
}

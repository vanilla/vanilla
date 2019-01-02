<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

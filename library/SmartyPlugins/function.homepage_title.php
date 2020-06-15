<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

use Vanilla\Formatting\Formats\HtmlFormat;

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
    return Gdn::formatService()->renderPlainText(c('Garden.HomepageTitle'), HtmlFormat::FORMAT_KEY);
}

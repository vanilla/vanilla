<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace IPBFormatter;

use Gdn;
use IPBFormatter\Formats\IPBFormat;

/**
 * Legacy-style formatter for IPB posts.
 */
class Formatter
{
    /**
     * Given a value, attempt to interpret it as IPB BBCode and render HTML.
     *
     * @param mixed $mixed
     * @return string
     */
    public function format($mixed): string
    {
        return Gdn::formatService()->renderHtml($mixed, IPBFormat::FORMAT_KEY);
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

/**
 * Class to hold an intermediary parsed format to speed up post formatting.
 */
interface FormatParsedInterface
{
    /**
     * Get the format we were parsed with.
     *
     * @return string
     */
    public function getFormatKey(): string;
}

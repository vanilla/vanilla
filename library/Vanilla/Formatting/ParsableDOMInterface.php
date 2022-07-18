<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Apply this interface to `TextFormatInterface` classes to show that they can be parsed into `TextDOMInterface` instances.
 */
interface ParsableDOMInterface
{
    /**
     * Parse a string into the DOM for this format.
     *
     * @param string $content The text to parse.
     * @return TextDOMInterface Returns the DOM for future processing.
     */
    public function parseDOM(string $content): TextDOMInterface;
}

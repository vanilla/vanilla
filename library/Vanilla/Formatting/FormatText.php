<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

/**
 * A data class that holds a string and its format.
 */
class FormatText
{
    /** @var string The text content. */
    public $text;

    /** @var string The text's format. */
    public $format;

    /**
     * FormatText constructor.
     *
     * @param string $text The text content.
     * @param string $format The text's content.
     */
    public function __construct(string $text, string $format)
    {
        $this->text = $text;
        $this->format = $format;
    }
}

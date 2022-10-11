<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

/**
 * Represents a text fragment that can be manipulated programmatically.
 */
interface TextFragmentInterface
{
    /**
     * Get the text of the fragment.
     *
     * @return string
     */
    public function getInnerContent(): string;

    /**
     * Set the text of the fragment.
     *
     * @param string $text
     * @return mixed
     */
    public function setInnerContent(string $text);

    /**
     * How the fragment's text is represented as one of the `TextFragmentType` constants.
     *
     * This is usually going to be `TextFragmentType::TEXT` or `TextFragmentType::HTML`.
     *
     * @return string
     */
    public function getFragmentType(): string;
}

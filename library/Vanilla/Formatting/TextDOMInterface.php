<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Represents the DOM for some format of text.
 *
 * This interface abstracts various text DOMs so that they can be updated programmatically and then serialized back.
 * You can think of this as a light weight adaptor to the built in `DOMDocument` that would wrap it for HTML documents.
 */
interface TextDOMInterface
{
    /**
     * Serialize the DOM back into its native string.
     *
     * @return FormatText
     */
    public function stringify(): FormatText;

    /**
     * Render the DOM out to HTML.
     *
     * @return string
     */
    public function renderHTML(): string;

    /**
     * Get the text fragments for this document.
     *
     * @return array<TextFragmentInterface|TextFragmentCollectionInterface>
     */
    public function getFragments(): array;
}

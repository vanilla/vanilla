<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Formats;

class Italic extends AbstractFormat
{
    /**
     * @inheritdoc
     */
    protected static function getAttributeLookupKey(): string
    {
        return "italic";
    }

    /**
     * @inheritdoc
     */
    protected function getBlackListedNestedFormats(): array
    {
        return [Bold::class, Link::class, Code::class];
    }

    /**
     * @inheritdoc
     */
    protected function getTagName(): string
    {
        return "em";
    }
}

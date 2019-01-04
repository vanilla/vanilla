<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Formats;

class Strike extends AbstractFormat {

    /**
     * @inheritDoc
     */
    protected static function getAttributeLookupKey(): string {
        return "strike";
    }

    /**
     * @inheritDoc
     */
    protected function getBlackListedNestedFormats(): array {
        return [
            Link::class,
            Code::class,
            Italic::class,
            Bold::class,
        ];
    }
    /**
     * @inheritDoc
     */
    protected function getTagName(): string {
        return "s";
    }
}

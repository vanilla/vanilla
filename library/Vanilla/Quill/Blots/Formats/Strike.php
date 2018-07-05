<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Formats;

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

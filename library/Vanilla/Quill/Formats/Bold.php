<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Formats;

class Bold extends AbstractFormat {

    /**
     * @inheritDoc
     */
    protected static function getBlackListedNestedFormats(): array {
        return [
            Link::class,
            Code::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function getTagName(): string {
        return "strong";
    }

    /**
     * @inheritDoc
     */
    protected static function getAttributeLookupKey(): string {
        return "bold";
    }
}

<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Formats;

class Link extends AbstractFormat {

    /**
     * @inheritDoc
     */
    protected static function getAttributeLookupKey(): string {
        return "link";
    }

    /**
     * @inheritDoc
     */
    protected function getBlackListedNestedFormats(): array {
        return [
            Code::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getTagName(): string {
        return "a";
    }

    /**
     * Get an attributes array for the blot's tag.
     */
    protected function getAttributes(): array {
        return [
            "href" => htmlspecialchars($this->currentOperation["attributes"]["link"]),
            "rel" => "nofollow",
        ];
    }
}

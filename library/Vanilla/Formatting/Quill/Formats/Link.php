<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Formats;

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
        $sanitizedLink = \Gdn_Format::sanitizeUrl(htmlspecialchars($this->currentOperation["attributes"]["link"]));
        return [
            "href" => $sanitizedLink,
            "rel" => "nofollow noreferrer ugc",
        ];
    }
}

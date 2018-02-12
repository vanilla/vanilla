<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Formats;

class Link extends AbstractFormat {

    /**
     * @inheritDoc
     */
    protected static function getBlackListedNestedFormats(): array {
        return [
            Code::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function getTagName(): string {
        return "a";
    }

    /**
     * @inheritDoc
     */
    protected static function getAttributeLookupKey(): string {
        return "link";
    }

    /**
     * @inheritDoc
     */
    public function getOpeningTag(): array {
        if (!static::matches([$this->previousOperation])) {
            return [
                "tag" => self::getTagName(),
                "attributes" => [
                    "href" => $this->currentOperation["attributes"]["link"],
                    "target" => "_blank",
                ],
            ];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getClosingTag(): array {
        if (!static::matches([$this->nextOperation])) {
            return [
                "tag" => static::getTagName(),
            ];
        }

        return [];
    }
}

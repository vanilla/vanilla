<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Formats;

use Vanilla\Quill\Block;
use Vanilla\Quill\Blots\AbstractBlot;

abstract class AbstractFormat extends AbstractBlot {

    /**
     * Get the formats allowed to be nested inside of this format.
     *
     * @return array
     */
    abstract protected static function getBlackListedNestedFormats(): array;

    /**
     * Get the name of the HTML tag for this blot.
     *
     * @return string
     */
    abstract protected static function getTagName(): string;

    /**
     * Get the string of the attribute key in the insert that determines if the blot applies or not. This key should lead to a boolean value in the attributes array of the insert.
     *
     * @return string
     */
    abstract protected static function getAttributeLookupKey(): string;

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }


    public function render(): string {
        return "";
    }

    public function shouldClearCurrentBlock(Block $block): bool {
        return false;
    }


    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        $result = false;

        foreach($operations as $op) {
            $attributes = val("attributes", $op, []);
            if (\array_key_exists(static::getAttributeLookupKey(), $attributes)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getOpeningTag(): array {
        $selfMatchesPrevious = static::matches([$this->previousOperation]);
        $matchesBlackListedFormat = false;
        foreach(static::getBlackListedNestedFormats() as $blackListedFormat) {
            if ($blackListedFormat::matches([$this->previousOperation, $this->currentOperation])) {
                $matchesBlackListedFormat = true;
                break;
            }
        }

        if (!$selfMatchesPrevious || $matchesBlackListedFormat) {
            return [
                "tag" => static::getTagName(),
            ];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getClosingTag(): array {
        $selfMatchesNext = static::matches([$this->nextOperation]);
        $matchesBlackListedFormat = false;
        foreach(static::getBlackListedNestedFormats() as $blackListedFormat) {
            if ($blackListedFormat::matches([$this->nextOperation, $this->currentOperation])) {
                $matchesBlackListedFormat = true;
                break;
            }
        }
        if (!$selfMatchesNext || $matchesBlackListedFormat) {
            return [
                "tag" => static::getTagName(),
            ];
        }

        return [];
    }
}

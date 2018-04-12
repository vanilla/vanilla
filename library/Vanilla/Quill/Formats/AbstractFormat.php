<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Formats;

use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\Blots\AbstractBlot;
use Vanilla\Quill\Blots\TextBlot;

/**
 * An different type of blot that can contain nested blots inside. Multiple of these can be active at the same time.
 *
 * @see TextBlot Usage of these blots can be found in the TextBlot class.
 *
 * @package Vanilla\Quill\Formats
 */
abstract class AbstractFormat extends AbstractBlot {

    /**
     * Get the formats allowed that cannot be "nested" inside this one.
     *
     * If a format is set here, and is also present on the before/after/current operation, then this format will
     * always create start/end html tags.
     *
     * @example
     * <strong>Bold only<em>Bold and italic</em><strong> is allowed.
     * <em>Italic only<strong>italic and bold</strong></em> is not.
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

    /**
     * @inheritDoc
     */
    public function render(): string {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
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
     * Get an attributes array for the blot's tag.
     */
    protected function getAttributes(): array {
        return [];
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
                "attributes" => $this->getAttributes(),
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

<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\BlotGroup;

/**
 * A blot to represent a list line terminator.
 */
class ListLineTerminatorBlot extends AbstractLineTerminatorBlot {

    const LIST_TYPE_BULLET = "bullet";
    const LIST_TYPE_ORDERED = "ordered";
    const LIST_TYPE_UNRECOGNIZED = "unrecognized list value";
    const LIST_TYPES = [self::LIST_TYPE_BULLET, self::LIST_TYPE_ORDERED];

    /**
     * @inheritdoc
     */
    public static function matches(array $operation): bool {
        $value = self::normalizeValue($operation ?? []);
        return in_array($value['type'], self::LIST_TYPES, true);
    }


    /**
     * Get a normalized value from the blot.
     *
     * @param array $operation The operation to normalize.
     *
     * @return array
     */
    private static function normalizeValue(array $operation): array {
        $blotValue = $operation["attributes"]["list"] ?? self::LIST_TYPE_UNRECOGNIZED;
        if (is_array($blotValue) && array_key_exists("depth", $blotValue) && array_key_exists("type", $blotValue)) {
            return $blotValue;
        }

        if (!is_string($blotValue)) {
            return [
                'type' => self::LIST_TYPE_UNRECOGNIZED,
                'depth' => 0,
            ];
        }

        return [
            'type' => $blotValue,
            'depth' => 0,
        ];
    }


    /**
     * @inheritdoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        $surroundingBlot = $group->getMainBlot();
        if ($surroundingBlot instanceof ListLineTerminatorBlot) {
            // If the list types are different we need to clear the block.
            return
                $surroundingBlot->getListType() !== $this->getListType()
                || $surroundingBlot->getListDepth() !== $this->getListDepth();
        } else {
            return parent::shouldClearCurrentGroup($group);
        }
    }
    /**
     * @inheritdoc
     */
    public function renderLineStart(): string {
        return "<li>";
    }

    /**
     * @inheritdoc
     */
    public function renderLineEnd(): string {
        return "</li>";
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        switch ($this->getListType()) {
            case static::LIST_TYPE_BULLET:
                return "<ul>";
            case static::LIST_TYPE_ORDERED:
                return "<ol>";
            default:
                return "";
        }
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        switch ($this->getListType()) {
            case static::LIST_TYPE_BULLET:
                return "</ul>";
            case static::LIST_TYPE_ORDERED:
                return "</ol>";
            default:
                return "";
        }
    }

    /**
     * Get a normalized value from the blot.
     *
     * @return array
     */
    private function getNormalizedValue(): array {
        return self::normalizeValue($this->currentOperation);
    }

    /**
     * Determine which type of list we are in.
     *
     * @return string
     */
    private function getListType(): string {
        $listType = $this->getNormalizedValue()['type'];
        return !in_array($listType, static::LIST_TYPES) ? static::LIST_TYPE_UNRECOGNIZED : $listType;
    }

    /**
     * @inheritdoc
     */
    public function getNestingDepth(): int {
        return $this->getListDepth();
    }

    /**
     * Determine which depth of nesting our list is.
     *
     * @return int
     */
    private function getListDepth(): int {
        return $this->getNormalizedValue()['depth'];
    }
}

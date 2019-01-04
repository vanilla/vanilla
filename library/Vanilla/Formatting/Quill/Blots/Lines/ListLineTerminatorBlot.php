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
        return static::opAttrsContainKeyWithValue($operation, "list", static::LIST_TYPES);
    }

    /**
     * @inheritdoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        $surroundingBlot = $group->getBlotForSurroundingTags();
        if ($surroundingBlot instanceof ListLineTerminatorBlot) {
            // If the list types are different we need to clear the block.
            return $surroundingBlot->getListType() !== $this->getListType();
        } else {
            return parent::shouldClearCurrentGroup($group);
        }
    }
    /**
     * @inheritdoc
     */
    public function renderLineStart(): string {
        $classString = "";
        $indentLevel = $this->currentOperation["attributes"]["indent"] ?? null;
        if ($indentLevel && filter_var($indentLevel, FILTER_VALIDATE_INT) !== false) {
            $classString = " class=\"ql-indent-$indentLevel\"";
        }

        return "<li$classString>";
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
     * Determine which type of list we are in.
     *
     * @return string
     */
    private function getListType() {
        $listType = $this->currentOperation["attributes"]["list"] ?? static::LIST_TYPE_UNRECOGNIZED;
        return !in_array($listType, static::LIST_TYPES) ? static::LIST_TYPE_UNRECOGNIZED : $listType;
    }
}

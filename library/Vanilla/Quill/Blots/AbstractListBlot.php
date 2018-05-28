<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\BlotGroup;

abstract class AbstractListBlot extends AbstractBlockBlot {

    /**
     * Get the type of list.
     *
     * @return string
     */
    abstract protected static function getListType(): string;

    /**
     * @inheritDoc
     */
    protected static function getAttributeKey(): string {
        return "list";
    }

    /**
     * @inheritDoc
     */
    protected static function getMatchingAttributeValue() {
        return static::getListType();
    }

    /**
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $classString = "";
        $indentLevel = valr("attributes.indent", $this->currentOperation)
            ?: valr("attributes.indent", $this->nextOperation);
        if ($indentLevel && filter_var($indentLevel, FILTER_VALIDATE_INT) !== false) {
            $classString = " class=\"ql-indent-$indentLevel\"";
        }

        return "<li$classString>" . parent::render() . "</li>";
    }
}

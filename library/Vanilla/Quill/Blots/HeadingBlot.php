<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

class HeadingBlot extends AbstractBlockBlot {

    /**
     * @inheritDoc
     */
    protected static function getAttributeKey(): string {
        return "header";
    }

    /**
     * @inheritDoc
     */
    protected static function getMatchingAttributeValue() {
        return [1, 2, 3, 4, 5, 6];
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "<h" . $this->getHeadingLevel() . ">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</h" . $this->getHeadingLevel() . ">";
    }

    /**
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * Get the heading level for the blot.
     *
     * @return int
     */
    private function getHeadingLevel(): int {
        return valr("attributes.header", $this->nextOperation);
    }
}

<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\BlotGroup;

class HeadingBlot extends AbstractBlockBlot {

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
    public static function isOwnGroup(): bool {
        return true;
    }

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
     * Get the heading level for the blot.
     *
     * @return int
     */
    private function getHeadingLevel(): int {
        return valr("attributes.header", $this->nextOperation);
    }
}

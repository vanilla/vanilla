<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

class OrderedListBlot extends AbstractListBlot {

    /**
     * @inheritDoc
     */
    protected static function getListType(): string {
        return "ordered";
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "<ol>";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</ol>";
    }
}

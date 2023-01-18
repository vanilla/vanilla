<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class ListNode extends AbstractNode
{
    /**
     * @inheritDoc
     */
    protected function getFormatString(): string
    {
        if (($this->data["type"] ?? null) === "ol") {
            return "<ol>%s</ol>";
        }
        return "<ul>%s</ul>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && in_array($node["type"], ["ul", "ol"], true);
    }
}

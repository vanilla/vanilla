<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class SpoilerContent extends AbstractNode
{
    /**
     * @inheritDoc
     */
    protected function getFormatString(): string
    {
        return '<div class="spoiler-content">%s</div>';
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "spoiler-content";
    }
}

<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Paragraph extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        return "<p>%s</p>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "p";
    }

    /**
     * @inheritDoc
     */
    protected function getTextEnd(): string
    {
        return "\n\n";
    }
}

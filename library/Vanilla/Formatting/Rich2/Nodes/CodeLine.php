<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class CodeLine extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        return "%s\n";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "code_line";
    }
}

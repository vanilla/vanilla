<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Table extends AbstractNode
{
    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return '<div class="tableWrapper"><table>';
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</table></div>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "table";
    }

    /**
     * @inheritDoc
     */
    protected function getTextEnd(): string
    {
        return "\n\n";
    }
}

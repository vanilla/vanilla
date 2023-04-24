<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Table extends AbstractNode
{
    const TYPE_KEY = "table";
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
    protected function getTextEnd(): string
    {
        return "\n\n";
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [TableBody::class, TableRow::class, TableHead::class, TableFoot::class, TableCaption::class];
    }
}

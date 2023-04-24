<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableRow extends AbstractNode
{
    const TYPE_KEY = "tr";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<tr>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</tr>";
    }

    /**
     * @inheritDoc
     */
    protected function getTextEnd(): string
    {
        return "\n";
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
        return [TableColumn::class, TableHeader::class];
    }
}

<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class OrderedList extends AbstractNode
{
    const TYPE_KEY = "ol";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "<ol>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</ol>";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }

    /**
     * @inheritdoc
     */
    public static function getExclusiveChildTypes(): array
    {
        return [ListItem::class];
    }
}

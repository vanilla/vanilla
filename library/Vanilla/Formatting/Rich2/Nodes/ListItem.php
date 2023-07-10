<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class ListItem extends AbstractNode
{
    const TYPE_KEY = "li";
    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<li>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</li>";
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
        return [ListItemChild::class];
    }
}

<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableColumn extends AbstractNode
{
    const TYPE_KEY = "td";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<td>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</td>";
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
        return [Text::class, Anchor::class, Paragraph::class, Heading::class, Mention::class, External::class];
    }
}

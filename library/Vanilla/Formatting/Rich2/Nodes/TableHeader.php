<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableHeader extends AbstractNode
{
    const TYPE_KEY = "th";
    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<th>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</th>";
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

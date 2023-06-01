<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableCaption extends AbstractNode
{
    const TYPE_KEY = "caption";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<caption>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</caption>";
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
        return [Text::class, Anchor::class, Paragraph::class, Heading::class];
    }
}

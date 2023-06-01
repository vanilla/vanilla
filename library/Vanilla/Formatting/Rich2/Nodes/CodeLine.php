<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class CodeLine extends AbstractNode
{
    const TYPE_KEY = "code_line";

    public bool $getChildren = false;

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
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
        return [Text::class, Anchor::class, Mention::class, External::class];
    }
}

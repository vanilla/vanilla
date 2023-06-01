<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Blank extends AbstractNode
{
    protected function getHtmlStart(): string
    {
        return "";
    }

    protected function getHtmlEnd(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return "blank";
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [];
    }
}

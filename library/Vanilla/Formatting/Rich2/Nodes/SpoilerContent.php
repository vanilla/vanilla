<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class SpoilerContent extends AbstractNode
{
    const TYPE_KEY = "spoiler-content";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return '<div class="spoiler-content">';
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</div>";
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
        return [SpoilerLine::class];
    }
}

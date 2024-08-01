<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class SpoilerLine extends AbstractNode
{
    const TYPE_KEY = "spoiler-item";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return '<p class="spoiler-line">';
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</p>";
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
}

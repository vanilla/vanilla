<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Paragraph extends AbstractNode
{
    const TYPE_KEY = "p";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "<p>";
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

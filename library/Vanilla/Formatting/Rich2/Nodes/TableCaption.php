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
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "<caption>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</caption>";
    }

    /**
     * @inheritdoc
     */
    protected function getTextEnd(): string
    {
        return "\n";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }
}

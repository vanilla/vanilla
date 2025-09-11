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
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "\n";
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

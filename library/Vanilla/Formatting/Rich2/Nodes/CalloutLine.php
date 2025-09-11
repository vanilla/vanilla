<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class CalloutLine extends AbstractNode
{
    const TYPE_KEY = "callout-item";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return '<p class="callout-line">';
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</p>";
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

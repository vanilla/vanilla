<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;
use Vanilla\Utility\HtmlUtils;

class TableHeader extends AbstractNode
{
    const TYPE_KEY = "th";
    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        $dataAttributes = $this->data["attributes"] ?? [];
        $attributes = HtmlUtils::attributes($dataAttributes);
        return "<th $attributes>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</th>";
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

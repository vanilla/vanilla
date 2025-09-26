<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;
use Vanilla\Utility\HtmlUtils;

class TableColumn extends AbstractNode
{
    const TYPE_KEY = "td";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        $dataAttributes = $this->data["attributes"] ?? [];
        $attributes = HtmlUtils::attributes($dataAttributes);
        return "<td $attributes>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</td>";
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

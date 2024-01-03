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
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $dataAttributes = $this->data["attributes"] ?? [];
        $attributes = HtmlUtils::attributes($dataAttributes);
        return "<th $attributes>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</th>";
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

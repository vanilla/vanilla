<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\FeatureFlagHelper;

class TableRow extends AbstractNode
{
    const TYPE_KEY = "tr";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        // so our tables in user content respect the customized heights per row
        if (
            FeatureFlagHelper::featureEnabled("RichTable") &&
            isset($this->data["actualHeight"]) &&
            $this->data["actualHeight"]
        ) {
            return '<tr style="height:' . htmlspecialchars((float) $this->data["actualHeight"]) . 'px">';
        }

        return "<tr>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</tr>";
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

    /**
     * @inheritdoc
     */
    public static function getExclusiveChildTypes(): array
    {
        return [TableColumn::class, TableHeader::class];
    }
}

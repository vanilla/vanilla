<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\FeatureFlagHelper;

class Table extends AbstractNode
{
    const TYPE_KEY = "table";
    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        // we need to add colgroup and 'customized' class name so our tables in user content respect the customized layout
        if (FeatureFlagHelper::featureEnabled("RichTable") && ($this->data["colSizes"] ?? false)) {
            $colGroup = "<colgroup>";
            foreach ($this->data["colSizes"] as $size) {
                $colGroup .= '<col style="min-width:80px;width:' . htmlspecialchars((float) $size) . 'px" />';
            }
            $colGroup .= '<col style="width:100%" /></colgroup>';

            return '<div class="tableWrapper customized" style="padding-left:' .
                htmlspecialchars((float) $this->data["marginLeft"] ?? 0) .
                'px"><table>' .
                $colGroup;
        }

        return '<div class="tableWrapper"><table>';
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</table></div>";
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
        return [TableBody::class, TableRow::class, TableHead::class, TableFoot::class, TableCaption::class];
    }
}

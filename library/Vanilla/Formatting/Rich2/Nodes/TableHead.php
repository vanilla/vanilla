<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableHead extends AbstractNode
{
    const TYPE_KEY = "thead";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "<thead>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</thead>";
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
        return [TableRow::class];
    }
}

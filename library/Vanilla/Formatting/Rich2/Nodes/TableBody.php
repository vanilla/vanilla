<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class TableBody extends AbstractNode
{
    const TYPE_KEY = "tbody";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "<tbody>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</tbody>";
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

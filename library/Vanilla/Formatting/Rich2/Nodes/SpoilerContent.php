<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class SpoilerContent extends AbstractNode
{
    const TYPE_KEY = "spoiler-content";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return '<div class="spoiler-content">';
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</div>";
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
        return [SpoilerLine::class];
    }
}

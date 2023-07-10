<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Utility\HtmlUtils;

class LegacyEmojiImage extends AbstractNode
{
    const TYPE_KEY = "legacy_emoji_image";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $dataAttributes = $this->data["attributes"];
        $dataAttributes["class"] = "emoji";
        $attributes = HtmlUtils::attributes($dataAttributes);
        return "<img $attributes />";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [Text::class];
    }
}

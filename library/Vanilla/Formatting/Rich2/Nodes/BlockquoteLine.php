<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Utility\HtmlUtils;

class BlockquoteLine extends AbstractNode
{
    const TYPE_KEY = "blockquote-line";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $class = HtmlUtils::attributes([
            "class" => $this->parseMode !== Parser::PARSE_MODE_QUOTE ? "blockquote-line" : null,
        ]);
        return "<p $class>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</p>";
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
        return [Text::class, Anchor::class, Mention::class, External::class];
    }
}

<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Utility\HtmlUtils;

class Blockquote extends AbstractNode
{
    /**
     * @inheritDoc
     */
    protected function getFormatString(): string
    {
        if ($this->parseMode === Parser::PARSE_MODE_QUOTE) {
            return "%s";
        }
        $wrapperClass = HtmlUtils::attributes(["class" => "blockquote"]);
        $contentClass = HtmlUtils::attributes(["class" => "blockquote-content"]);
        return "<div $wrapperClass><div $contentClass>%s</div></div>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "blockquote";
    }
}

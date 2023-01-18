<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Utility\HtmlUtils;

class Text extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["text"]);
    }

    /**
     * @inheritDoc
     */
    public function renderText(): string
    {
        return $this->data["text"];
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        $text = nl2br(htmlspecialchars($this->data["text"]));

        if ($this->data["bold"] ?? false) {
            $text = wrap($text, "strong");
        }
        if ($this->data["italic"] ?? false) {
            $text = wrap($text, "em");
        }
        if ($this->data["strikethrough"] ?? false) {
            $text = wrap($text, "s");
        }
        if ($this->data["code"] ?? false) {
            $text = wrap(
                $text,
                "code",
                HtmlUtils::attributes(["class" => "code codeInline", "spellcheck" => "false", "tabindex" => "0"])
            );
        }

        return $text;
    }

    /**
     * Set the text for this text node
     *
     * @param string $text
     * @return void
     */
    public function setText(string $text)
    {
        $this->data["text"] = $text;
    }
}

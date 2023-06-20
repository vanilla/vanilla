<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;
use Vanilla\Utility\HtmlUtils;

class Text extends AbstractLeafNode implements TextFragmentInterface
{
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
    public function renderTextContent(): string
    {
        return $this->data["text"];
    }

    /**
     * @inheritDoc
     */
    public function renderHtmlContent(): string
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

    /**
     * @inheritDoc
     */
    public function getFragmentType(): string
    {
        return TextFragmentType::TEXT;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string
    {
        return $this->renderTextContent();
    }

    /**
     * @inheritDoc
     */
    public function setInnerContent(string $text)
    {
        $this->setText($text);
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return "text";
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [];
    }

    public static function create(): AbstractNode
    {
        return new self([
            "text" => "",
        ]);
    }
}

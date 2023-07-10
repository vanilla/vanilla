<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Utility\HtmlUtils;

class CodeBlock extends AbstractNode
{
    const TYPE_KEY = "code_block";

    public bool $getChildren = false;

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $lang = isset($this->data["lang"]) ? "language-{$this->data["lang"]}" : "";
        $attributes = HtmlUtils::attributes([
            "class" => "code codeBlock $lang",
            "spellcheck" => "false",
            "tabindex" => "0",
        ]);
        return "<pre $attributes>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</pre>";
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
        return [CodeLine::class];
    }
}

<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Web\TwigRenderTrait;

class Callout extends AbstractNode
{
    use TwigRenderTrait;

    const TYPE_KEY = "callout";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return '<div class="callout ' .
            htmlspecialchars($this->data["appearance"]) .
            '">' .
            $this->renderTwig("@library/Vanilla/Formatting/CalloutIcon.twig", [
                "appearance" => $this->data["appearance"],
            ]) .
            "<div>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</div></div>";
    }

    /**
     * @inheritdoc
     */
    protected function getTextStart(): string
    {
        $appearance = $this->data["appearance"] ?? "info";
        return "[$appearance callout] ";
    }

    /**
     * @inheritdoc
     */
    protected function getTextEnd(): string
    {
        return "\n";
    }

    public function renderText(): string
    {
        return $this->getTextStart() . $this->children->renderText() . $this->getTextEnd();
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
        return [CalloutLine::class];
    }
}

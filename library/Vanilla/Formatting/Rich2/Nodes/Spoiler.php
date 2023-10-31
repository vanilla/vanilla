<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Web\TwigRenderTrait;

class Spoiler extends AbstractNode
{
    use TwigRenderTrait;

    const TYPE_KEY = "spoiler";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $button = $this->renderTwig("@library/Vanilla/Formatting/SpoilerToggle.twig", []);
        return '<div class="spoiler">' . $button;
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "</div>";
    }

    public function renderText(): string
    {
        return "(Spoiler)\n";
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
    public static function getExclusiveChildTypes(): array
    {
        return [SpoilerContent::class];
    }
}

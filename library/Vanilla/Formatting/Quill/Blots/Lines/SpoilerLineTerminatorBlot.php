<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\Parser;
use Vanilla\Web\TwigRenderTrait;

/**
 * A blot to represent a spoiler line terminator.
 */
class SpoilerLineTerminatorBlot extends AbstractLineTerminatorBlot
{
    use TwigRenderTrait;

    /**
     * @inheritdoc
     */
    public static function matches(array $operation): bool
    {
        return static::opAttrsContainKeyWithValue($operation, "spoiler-line");
    }

    /**
     * @inheritdoc
     */
    public function getGroupOpeningTag(): string
    {
        $wrapperClass = "spoiler";
        $contentClass = "spoiler-content";
        $button = $this->renderTwig("@library/Vanilla/Formatting/SpoilerToggle.twig", []);

        return "<div class=\"$wrapperClass\">$button<div class=\"$contentClass\">";
    }

    /**
     * @inheritdoc
     */
    public function getGroupClosingTag(): string
    {
        return "</div></div>";
    }

    /**
     * @inheritdoc
     */
    public function renderLineStart(): string
    {
        return '<p class="spoiler-line">';
    }

    /**
     * @inheritdoc
     */
    public function renderLineEnd(): string
    {
        return "</p>";
    }
}

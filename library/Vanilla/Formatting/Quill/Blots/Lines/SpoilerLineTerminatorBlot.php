<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\Parser;

/**
 * A blot to represent a spoiler line terminator.
 */
class SpoilerLineTerminatorBlot extends AbstractLineTerminatorBlot {

    /**
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        return static::opAttrsContainKeyWithValue($operation, "spoiler-line");
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        $wrapperClass = "spoiler";
        $contentClass = "spoiler-content";
        $button = $this->getToggleButton();

        return "<div class=\"$wrapperClass\">$button<div class=\"$contentClass\">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</div></div>";
    }

    /**
     * @inheritDoc
     */
    public function renderLineStart(): string {
        return '<p class="spoiler-line">';
    }

    /**
     * @inheritDoc
     */
    public function renderLineEnd(): string {
        return '</p>';
    }

    /**
     * Get the HTML for the toggle button of the spoiler group.
     *
     * @return string
     */
    private function getToggleButton(): string {
        $t = 't';
        $buttonClasses = "iconButton button-spoiler";
        $buttonDisabled = "disabled";
        $chevron = "";
        if ($this->parseMode === Parser::PARSE_MODE_NORMAL) {
            $buttonClasses .= " js-toggleSpoiler";
            $buttonDisabled = "";
            $chevron = <<<HTML
<span class="spoiler-chevron">
    <svg class="icon spoiler-chevronUp">
        <title>▲</title>
        <path fill="currentColor" d="M0,3.6c0-0.1,0-0.2,0.1-0.3l3.5-3.1C3.7,0,3.9,0,4,0c0.1,0,0.3,0,0.4,0.1l3.5,3.1C8,3.3,8,3.4,8,3.6s0,0.2-0.1,0.3C7.8,4,7.6,4,7.5,4h-7C0.4,4,0.2,4,0.1,3.9C0,3.8,0,3.7,0,3.6z"></path>
    </svg>
    <svg class="icon spoiler-chevronDown">
        <title>▼</title>
        <path fill="currentColor" d="M8,3.55555556 C8,3.43518519 7.95052083,3.33101852 7.8515625,3.24305556 L4.3515625,0.131944444 C4.25260417,0.0439814815 4.13541667,0 4,0 C3.86458333,0 3.74739583,0.0439814815 3.6484375,0.131944444 L0.1484375,3.24305556 C0.0494791667,3.33101852 -4.4408921e-16,3.43518519 -4.4408921e-16,3.55555556 C-4.4408921e-16,3.67592593 0.0494791667,3.78009259 0.1484375,3.86805556 C0.247395833,3.95601852 0.364583333,4 0.5,4 L7.5,4 C7.63541667,4 7.75260417,3.95601852 7.8515625,3.86805556 C7.95052083,3.78009259 8,3.67592593 8,3.55555556 Z" transform="matrix(1 0 0 -1 0 4)"></path>
    </svg>
</span>
HTML;
        }
        return <<<HTML
<div contenteditable="false" class="spoiler-buttonContainer">
<button class="$buttonClasses" $buttonDisabled>
    <span class="spoiler-warning">
        <span class="spoiler-warningMain">
            <svg class="icon spoiler-icon" viewBox="0 0 24 24">
                <title>{$t('Spoiler')}</title>
                <path d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z"
                    style="currentColor"
                />
                <polygon
                    points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146"
                    style="currentColor"
                />
            </svg>
            <strong class="spoiler-warningBefore">
                {$t('Spoiler Warning')}
            </strong>
        </span>
        $chevron
    </span>
</button></div>
HTML;
    }
}

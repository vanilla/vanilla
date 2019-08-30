<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
    <svg class="spoiler-chevronUp" viewBox="0 0 20 20">
        <title>▲</title>
        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(-90 9.857 10.429)"/>
    </svg>
    <svg class="spoiler-chevronDown" viewBox="0 0 20 20">
        <title>▼</title>
        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"/>
    </svg>
</span>
HTML;
        }
        return <<<HTML
<div contenteditable="false" class="spoiler-buttonContainer">
<button title="{$t('Toggle Spoiler')}" class="$buttonClasses" $buttonDisabled>
    <span class="spoiler-warning">
        <span class="spoiler-warningMain">
            <svg class="spoiler-icon" viewBox="0 0 24 24">
                <title>{$t('Spoiler')}</title>
                <path fill="currentColor" d="M11.469 15.47c-2.795-.313-4.73-3.017-4.06-5.8l4.06 5.8zM12 16.611a9.65 
                9.65 0 0 1-8.333-4.722 9.569 9.569 0 0 1 3.067-3.183L5.778 7.34a11.235 11.235 0 0 0-3.547 3.703 1.667 
                1.667 0 0 0 0 1.692A11.318 11.318 0 0 0 12 18.278c.46 0 .92-.028 1.377-.082l-1.112-1.589a9.867 9.867 
                0 0 1-.265.004zm9.77-3.876a11.267 11.267 0 0 1-4.985 4.496l1.67 2.387a.417.417 0 0 1-.102.58l-.72.504a.417.417 
                0 0 1-.58-.102L5.545 4.16a.417.417 0 0 1 .102-.58l.72-.505a.417.417 0 0 1 .58.103l1.928 2.754A11.453 11.453 0 
                0 1 12 5.5c4.162 0 7.812 2.222 9.77 5.543.307.522.307 1.17 0 1.692zm-1.437-.846A9.638 9.638 0 0 0 12.828 
                7.2a1.944 1.944 0 1 0 3.339 1.354 4.722 4.722 0 0 1-1.283 5.962l.927 1.324a9.602 9.602 0 0 0 4.522-3.952z"/>
            </svg>
            <span class="spoiler-warningLabel">
                {$t('Spoiler Warning')}
            </span>
        </span>
        $chevron
    </span>
</button></div>
HTML;
    }
}

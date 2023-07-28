<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

/**
 * Plain old data object to store pattern-replacement pairs and call preg_replace against them.
 */
class FormatRegexReplacements
{
    protected array $patterns = [];

    protected array $replacements = [];

    /**
     * Adds a pattern-replacement pair
     *
     * @param string $pattern The pattern to match.
     * @param string $replacement The replacement string to use.
     * @return void
     */
    public function addReplacement(string $pattern = "", string $replacement = "")
    {
        $this->patterns[] = $pattern;
        $this->replacements[] = $replacement;
    }

    /**
     * Calls preg_replace using the stored pattern-replacement pairs
     *
     * @param string $body
     * @return string
     */
    public function replace(string $body): string
    {
        return preg_replace($this->patterns, $this->replacements, $body);
    }
}

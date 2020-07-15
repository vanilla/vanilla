<?php
/**
 * @author Isis Graziatto<isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace CivilTongueEx\Library;

/**
 * Class ContentFilter
 *
 * @package CivilTongueEx\Library
 */
class ContentFilter {

    /** @var string? */
    private $replacement;

    /** @var string? */
    private $words;

    /**
     * Replace black-listed words according to pattern
     *
     * @param string $text
     * @return ?string
     */
    public function replace($text = ''): ?string {
        if (!isset($text)) {
            return $text;
        }

        $patterns = $this->getPatterns();
        $result = preg_replace($patterns, $this->replacement, $text);
        return $result;
    }

    /**
     * Get patterns
     *
     * @return array
     */
    public function getPatterns(): array {
        static $patterns = null;

        if ($patterns === null) {
            $patterns = [];
            $words = $this->words;
            if ($words !== null) {
                $explodedWords = explode(';', $words);
                foreach ($explodedWords as $word) {
                    if (trim($word)) {
                        $patterns[] = '`(?<![\pL\pN])'.preg_quote(trim($word), '`').'(?![\pL\pN])`isu';
                    }
                }
            }
        }
        return $patterns;
    }

    /**
     * Get replacement
     *
     * @return string
     */
    public function getReplacement(): string {
        return $this->replacement;
    }

    /**
     * Get words
     *
     * @return string
     */
    public function getWords(): string {
        return $this->words;
    }

    /**
     * Set replacement
     *
     * @param string $replacement
     */
    public function setReplacement($replacement) {
        $this->replacement = $replacement;
    }

    /**
     * Set words
     *
     * @param string $words
     */
    public function setWords($words) {
        $this->words = $words;
    }
}

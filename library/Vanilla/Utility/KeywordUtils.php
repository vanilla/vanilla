<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Utilities for matching keywords.
 */
final class KeywordUtils
{
    /**
     * Checks if a string of text matches an array of words.
     *
     * @param string $text The string of text to search for a match.
     * @param array $needles The strings/words to search a match for.
     * @return string|false Returns string (matching needle) if one of the needles is in the text of **false** otherwise.
     */
    public static function checkMatch(string $text, array $needles): string|false
    {
        if (empty($needles)) {
            return false;
        }

        $text = self::splitWords($text);
        if ($text === false) {
            return false;
        }

        $needlePhrases = [];
        $needleWords = [];
        foreach ($needles as $needle) {
            $words = self::splitWords(strtolower($needle));
            $needleWords[$words[0]][] = $words;
            $needlePhrases[$words[0]] = $needle;
        }

        foreach ($text as $i => $word) {
            $word = strtolower($word);
            if ($wordPos = self::needleStartsWithWord($word, $needleWords)) {
                foreach ($needleWords[$wordPos] as $phrase) {
                    $j = $i;
                    $matched = true;
                    foreach ($phrase as $phraseWord) {
                        $textWord = $text[$j] ?? null;
                        if ($textWord === null || self::matchesWord($textWord, $phraseWord) === false) {
                            $matched = false;
                            break;
                        }
                        $j++;
                    }
                    if ($matched) {
                        return $needlePhrases[$wordPos];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Do we have any needles starting with the provided word?
     *
     * @param string $word
     * @param array $needles The array of needles.
     *
     * @return string|null Form of word that matches the start of a phrase. Otherwise, null.
     */
    private static function needleStartsWithWord(string $word, array $needles): ?string
    {
        if (isset($needles[$word])) {
            return $word;
        } else {
            $word = self::normalizeWord($word);
            if (isset($needles[$word])) {
                return $word;
            }
        }
        return null;
    }

    /**
     * Normalize words for comparison.
     *
     * @param string $text
     * @return string
     */
    private static function normalizeWord(string $text): string
    {
        $result = strtr($text, ['$' => "s"]);
        return $result;
    }

    /**
     * Determine if text matches a particular "needle" word, accounting for normalization of the text.
     *
     * @param string $text
     * @param string $needle
     * @return bool
     */
    private static function matchesWord(string $text, string $needle): bool
    {
        if (strcasecmp($text, $needle) === 0) {
            return true;
        } elseif (strcasecmp(self::normalizeWord($text), $needle) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Split text into individual tokens.
     *
     * @param string $text
     * @return array
     */
    private static function splitWords(string $text): array
    {
        $result = preg_split('/[\s\[\]¡!"\'()+,.\/:;<=>¿?^_`{|}~\-]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return $result;
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


/**
 * Provides methods to make words plural or singular.
 *
 * These functions are not meant to be exhaustive, but when they can't handle a word that code needs they should be fixed.
 */
trait PluralizationTrait {
    private $pluralExceptions = ['children' => 'child', 'people' => 'person'];

    /**
     * Present the singular version of a word.
     *
     * This is currently used just to determine default template names controller slugs. It is not meant to be exhaustive
     * for every word, but can be extended or overridden by controllers.
     *
     * @param string $word The word to make singular.
     * @return string Returns a singular word or `$word` if a singular form cannot be determined.
     */
    protected function singular($word) {
        $test = mb_strtolower($word);

        if (isset($this->pluralExceptions[$test])) {
            $result = $this->pluralExceptions[$test];
        } elseif (strpos($test, 'ies', -3) !== false) {
            $result = substr($test, 0, -3).'y';
        } elseif (preg_match('`(ss|sh|ch|x|z)es$`', $test)) {
            $result = substr($test, 0, -2);
        } elseif (strpos($test, 's', -1) !== false) {
            $result = substr($test, 0, -1);
        } else {
            $result = $test;
        }

        $c = mb_substr($word, 0, -1);
        if (mb_strtoupper($c) === $c) {
            $result = mb_strtoupper(mb_substr($result, 0, 1)).mb_substr($result, -1);
        }

        return $result;
    }

    /**
     * Present the plural version of a word.
     *
     * @param string $word The word to make singular.
     * @return string Returns a singular word or `$word` if a singular form cannot be determined.
     */
    protected function plural($word) {
        $test = mb_strtolower($word);

        if (false !== $plural = array_search($test, $this->pluralExceptions)) {
            $result = $plural;
        } elseif (strpos($test, 'y', -1) !== false) {
            $result = substr($test, 0, -1).'ies';
        } elseif (preg_match('`(ss|sh|ch|x|z)$`', $test)) {
            $result = $test.'es';
        } elseif (strpos($test, 's', -1) === false) {
            $result = $test.'s';
        } else {
            $result = $test;
        }

        $c = mb_substr($word, 0, -1);
        if (mb_strtoupper($c) === $c) {
            $result = mb_strtoupper(mb_substr($result, 0, 1)).mb_substr($result, -1);
        }

        return $result;
    }
}

<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Formatting;

 /**
  * Static utilities for text formatting.
  *
  * Do NOT put any stateful logic in this file.
  */
class FormatUtil {
    /**
     * Do a preg_replace, but don't affect things inside <code> tags.
     *
     * The three parameters are identical to the ones you'd pass
     * preg_replace.
     *
     * @param mixed $search The value being searched for, just like in
     *              preg_replace or preg_replace_callback.
     * @param string|callable $replace The replacement value, just like in
     *              preg_replace or preg_replace_callback.
     * @param string $subject The string being searched.
     * @param bool $isCallback If true, do preg_replace_callback. Do
     *             preg_replace otherwise.
     * @return string
     */
    public static function replaceButProtectCodeBlocks(string $search, $replace, string $subject, bool $isCallback = false): string {
        // Take the code blocks out, replace with a hash of the string, and
        // keep track of what substring got replaced with what hash.
        $codeBlockContents = [];
        $codeBlockHashes = [];
        $subject = preg_replace_callback(
            '/<code.*?>.*?<\/code>/is',
            function ($matches) use (&$codeBlockContents, &$codeBlockHashes) {
                // Surrounded by whitespace to try to prevent the characters
                // from being picked up by $Pattern.
                $replacementString = ' '.sha1($matches[0]).' ';
                $codeBlockContents[] = $matches[0];
                $codeBlockHashes[] = $replacementString;
                return $replacementString;
            },
            $subject
        );

        // Do the requested replacement.
        if ($isCallback) {
            $subject = preg_replace_callback($search, $replace, $subject);
        } else {
            $subject = preg_replace($search, $replace, $subject);
        }

        // Put back the code blocks.
        $subject = str_replace($codeBlockHashes, $codeBlockContents, $subject);

        return $subject;
    }
}

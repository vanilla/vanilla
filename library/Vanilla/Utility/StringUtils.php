<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * A collection of string utilities.
 */
final class StringUtils {
    /**
     * Encode a value as JSON or throw an exception on error.
     *
     * @param mixed $value
     * @param int|null $options
     * @return string
     * @throws \Exception If an error was encountered during encoding.
     */
    public static function jsonEncodeChecked($value, $options = null) {
        if ($options === null) {
            $options = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;
        }
        $encoded = json_encode($value, $options);
        $errorMessage = null;
        switch (json_last_error()) {
            case \JSON_ERROR_NONE:
                // Do absolutely nothing since all went well!
                break;
            case \JSON_ERROR_UTF8:
                $errorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            case \JSON_ERROR_RECURSION:
                $errorMessage = 'One or more recursive references in the value to be encoded.';
                break;
            case \JSON_ERROR_INF_OR_NAN:
                $errorMessage = 'One or more NAN or INF values in the value to be encoded';
                break;
            case \JSON_ERROR_UNSUPPORTED_TYPE:
                $errorMessage = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $errorMessage = 'An unknown error has occurred.';
        }
        if ($errorMessage !== null) {
            throw new \Exception("JSON encoding error: {$errorMessage}", 500);
        }
        return $encoded;
    }

    /**
     * Remove a substring from the beginning of the string.
     *
     * @param string $str The string to search.
     * @param string $trim The substring to trim off the search string.
     * @param bool $caseInsensitive Whether or not to do a case insensitive comparison.
     * @return string Returns the trimmed string.
     */
    public static function substringLeftTrim(string $str, string $trim, bool $caseInsensitive = false): string {
        if (strlen($str) < strlen($trim)) {
            return $str;
        } elseif (substr_compare($str, $trim, 0, strlen($trim), $caseInsensitive) === 0) {
            return substr($str, strlen($trim));
        } else {
            return $str;
        }
    }

    /**
     * Remove a substring from the end of the string.
     *
     * @param string $str The string to search.
     * @param string $trim The substring to trim off the search string.
     * @param bool $caseInsensitive Whether or not to do a case insensitive comparison.
     * @return string Returns the trimmed string.
     */
    public static function substringRightTrim(string $str, string $trim, bool $caseInsensitive = false): string {
        if (strlen($str) < strlen($trim)) {
            return $str;
        } elseif (substr_compare($str, $trim, -strlen($trim), null, $caseInsensitive) === 0) {
            return substr($str, 0, -strlen($trim));
        } else {
            return $str;
        }
    }

    /**
     * Make a variable name title case for putting into a label.
     *
     * This method supports strings in the following formats.
     *
     * - camelCase
     * - PascalCase
     * - kebab-case
     * - snake_case
     * - space separated
     *
     * @param string $str The variable name to labelize.
     * @return string
     */
    public static function labelize(string $str): string {
        $str = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $str);
        $str = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $str);
        $str = preg_replace('`[_-]`', ' ', $str);
        $str = preg_replace('`\s+`', ' ', $str);
        $str = implode(' ', array_map('ucfirst', explode(' ', $str)));

        return $str;
    }

    /**
     * Check if a string contains another string.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool {
        if (empty($needle)) {
            return false;
        }
        return strpos($haystack, $needle) !== false;
    }
}

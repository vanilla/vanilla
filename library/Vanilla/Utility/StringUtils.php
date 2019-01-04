<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * A collection of string utilities.
 */
class StringUtils {
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
}

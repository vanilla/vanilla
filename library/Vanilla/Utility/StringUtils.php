<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Firebase\JWT\JWT;
use function Sodium\add;

/**
 * A collection of string utilities.
 */
final class StringUtils
{
    /**
     * Encode a value as JSON or throw an exception on error.
     *
     * @param mixed $value
     * @param int|null $options
     * @return string
     * @throws \Exception If an error was encountered during encoding.
     */
    public static function jsonEncodeChecked($value, $options = null)
    {
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
                $errorMessage = "Malformed UTF-8 characters, possibly incorrectly encoded";
                break;
            case \JSON_ERROR_RECURSION:
                $errorMessage = "One or more recursive references in the value to be encoded.";
                break;
            case \JSON_ERROR_INF_OR_NAN:
                $errorMessage = "One or more NAN or INF values in the value to be encoded";
                break;
            case \JSON_ERROR_UNSUPPORTED_TYPE:
                $errorMessage = "A value of a type that cannot be encoded was given.";
                break;
            default:
                $errorMessage = "An unknown error has occurred.";
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
    public static function substringLeftTrim(string $str, string $trim, bool $caseInsensitive = false): string
    {
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
    public static function substringRightTrim(string $str, string $trim, bool $caseInsensitive = false): string
    {
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
    public static function labelize(string $str): string
    {
        $str = preg_replace("`(?<![A-Z0-9])([A-Z0-9])`", ' $1', $str);
        $str = preg_replace("`([A-Z0-9])(?=[a-z])`", ' $1', $str);
        $str = preg_replace("`[_-]`", " ", $str);
        $str = preg_replace("`\s+`", " ", $str);
        $str = implode(" ", array_map("ucfirst", explode(" ", $str)));

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
    public static function contains(string $haystack, string $needle): bool
    {
        if (empty($needle)) {
            return false;
        }
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Take a formatted size (e.g. 128M) and convert it to bytes.
     *
     * @param string $formatted
     * @return int|bool
     */
    public static function unformatSize(string $formatted)
    {
        $units = [
            "B" => 1,
            "K" => 1024,
            "M" => 1024 * 1024,
            "G" => 1024 * 1024 * 1024,
            "T" => 1024 * 1024 * 1024 * 1024,
        ];

        if (preg_match("/([0-9.]+)\s*([A-Z]*)/i", $formatted, $matches)) {
            $number = floatval($matches[1]);
            $unit = strtoupper(substr($matches[2], 0, 1));
            $mult = val($unit, $units, 1);

            $result = round($number * $mult, 0);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Strip unicode whitespace and trim the value.
     *
     * Regex taken from: https://stackoverflow.com/a/64808243
     *
     * @param string $text
     * @return string
     */
    public static function stripUnicodeWhitespace(string $text)
    {
        $text = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$|(?![ \r\n])[\pZ\pC]/u', "", $text);
        $text = trim($text);
        return $text;
    }

    /**
     * Turn the Value into a CSV.
     *
     * @param array $value
     * @return string
     */
    public static function encodeCSV($value)
    {
        $result = [];

        if (empty($value)) {
            throw new \Exception("Value to convert into CSV is empty.", 500);
        }

        // Single result must be put inside an array to be converted into CSV properly.
        if (!isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $row) {
            $result[] = self::flattenArray($row);
        }

        try {
            // Open a buffer to store the CSV while we generate it.
            $fp = fopen("php://temp", "w+");

            $headings = self::getCsvHeading($result);
            $result = self::normalizeCsvRows($result, $headings);
            fputcsv($fp, $headings);

            foreach ($result as $fields) {
                fputcsv($fp, $fields);
            }

            // Set the pointer back to the start
            rewind($fp);
            $csv_contents = stream_get_contents($fp);
        } finally {
            fclose($fp);
        }

        return $csv_contents;
    }

    /**
     * Flatten the array. Sequential keys will be converted into a JSON array.
     *
     * @param $rows
     * @param string $prefix
     * @return array|mixed
     */
    public static function flattenArray($rows, $prefix = "")
    {
        $result = [];
        foreach ($rows as $key => $value) {
            if (is_array($value) && !ArrayUtils::isAssociative($value)) {
                // Check if array is sequential.
                $result[$prefix . $key] = json_encode($value);
            } elseif (is_array($value)) {
                $result = $result + self::flattenArray($value, $prefix . $key . ".");
            } else {
                if (is_string($value)) {
                    // Remove escaped quotes since those are not processed by the CSV generator.
                    $value = str_replace("\\\"", "\"", $value);

                    // Escape next line to prevent text from breaking.
                    $value = str_replace("\n", "\\n", $value);
                }
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    /**
     * Return a CSV heading based on the keys of every row.
     *
     * @param array $rows
     * @return array<string>
     */
    public static function getCsvHeading(array $rows)
    {
        $headingsHashIndex = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $rowKey) {
                $headingsHashIndex[$rowKey] = null;
            }
        }

        $headings = array_keys($headingsHashIndex);

        // Filter out subkeys
        $headings = array_filter($headings, function (string $heading) use ($headings) {
            foreach ($headings as $headingToCheck) {
                if (str_starts_with($headingToCheck, $heading . ".")) {
                    // There a heading that is a "child" of us. We should not include this value.
                    return false;
                }
            }

            return true;
        });

        sort($headings);

        return $headings;
    }

    /**
     * Normalize a CSV array by setting missing fields to $defaultValue.
     *
     * @param array $rows
     * @param string[] $headings
     * @param null $defaultValue
     * @return array
     */
    public static function normalizeCsvRows(array $rows, array $headings, $defaultValue = null)
    {
        $result = [];

        foreach ($rows as $row) {
            $newRow = [];
            foreach ($headings as $heading) {
                $newRow[$heading] = $row[$heading] ?? null;
            }
            ksort($newRow);
            $result[] = $newRow;
        }

        return $result;
    }

    /**
     * Decode a JWT payload.
     *
     * @param string $rawJwt
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public static function decodeJwtPayload(string $rawJwt): array
    {
        $jwtPieces = explode(".", $rawJwt);
        if (count($jwtPieces) !== 3) {
            throw new \UnexpectedValueException("Wrong number of segments");
        }
        $rawPayload = $jwtPieces[1];
        $payload = json_decode(JWT::urlsafeB64Decode($rawPayload), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException("json_decode error: " . json_last_error_msg());
        }
        return $payload;
    }

    /**
     * Parse an urlCode, as provided from CategoryListpageController's `get()` (aka without the `/categories` part) out of a path.
     *
     * @param string|null $path The path to parse (doesn't include the `/categories`. part.
     * @return string|null The parsed ID or null.
     * @internal Marked public for testing.
     */
    public static function parseUrlCodeFromPath(?string $path): ?string
    {
        /** Regex pattern for retrieving the record ID from a URL path. */
        $URL_PATH_PATTERN = "/([^\/]*)?^\/(?<urlPath>[^\/\\?<>,.'\"]+).*/";

        if (!$path) {
            return null;
        }

        $matches = [];
        if (preg_match($URL_PATH_PATTERN, $path, $matches) === 0) {
            return null;
        }

        $urlCode = filter_var($matches["urlPath"], FILTER_SANITIZE_STRING);

        if ($urlCode === false) {
            return null;
        }

        return strtolower($urlCode);
    }

    /**
     * Parse an ID out of a path with the formulation /:id-:slug.
     * -:slug is optional.
     *
     * @param string|null $path The path to parse.
     *
     * @return int|null The parsed ID or null.
     * @internal Marked public for testing.
     */
    public static function parseIDFromPath(?string $path, string $pathSeparator = "-"): ?int
    {
        /** Regex pattern for retrieving the record ID from a URL path. */
        $ID_PATH_PATTERN = "/^\/(?<recordID>\d+)({$pathSeparator}[^\/]*)?.*/";

        if (!$path) {
            return null;
        }

        $matches = [];
        if (preg_match($ID_PATH_PATTERN, $path, $matches) === 0) {
            return null;
        }

        $id = filter_var($matches["recordID"], FILTER_VALIDATE_INT);

        if ($id === false) {
            return null;
        }

        return $id;
    }

    /**
     * Get the page number out of some path.
     *
     * Works in the following format:
     * "/path/some/path" -> 1
     * "/path/some/path/p2" -> 2
     * "/path/some/path/p142" -> 142
     *
     * @param string|null $path
     *
     * @return int
     * @internal Marked public for testing.
     */
    public static function parsePageNumberFromPath(?string $path): int
    {
        $PAGE_PATH_PATTERN = "/.*\/p(?<pageNumber>\d+)$/";
        if (!$path) {
            return 1;
        }

        $matches = [];
        if (preg_match($PAGE_PATH_PATTERN, $path, $matches) === 0) {
            return 1;
        }

        $pageNumber = filter_var($matches["pageNumber"], FILTER_VALIDATE_INT);

        if ($pageNumber === false) {
            return 1;
        }

        return $pageNumber;
    }

    /**
     * Sanitize an exception message to prevent displaying path ref.
     * The primary case here is PHP TypeErrors which have file paths directly in them.
     *
     * @param string $message actual exception message.
     * @return string modified exception.
     */
    public static function sanitizeExceptionMessage(string $message): string
    {
        return str_replace(PATH_ROOT, "", $message);
    }

    /**
     * Parse out all URLs referenced in the font.css file.
     *
     * @param string $css
     *
     * @return array
     */
    public static function parseCssUrls(string $css)
    {
        preg_match_all("/url\\(([^)]+)\\)/", $css, $matches);
        $results = [];
        foreach ($matches[1] as $match) {
            $targetName = pathinfo($match, PATHINFO_BASENAME);
            $results[$targetName] = $match;
        }
        return $results;
    }
}

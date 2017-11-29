<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\Schema\Schema;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\CapitalCaseScheme;

class ApiUtils {

    /**
     * Convert array keys for functions that aren't compatible with camelCase.
     *
     * @param array $input API input (query, request body, etc.)
     * @return array An array with CapitalCase keys.
     */
    public static function convertInputKeys(array $input) {
        static $scheme;

        if ($scheme === null) {
            $scheme = new CapitalCaseScheme();
        }

        $result = $scheme->convertArrayKeys($input);
        return $result;
    }

    /**
     * Convert array keys for standard API output.
     *
     * @param array $output A single row as part of an API response.
     * @return array An array with camelCase keys.
     */
    public static function convertOutputKeys(array $output) {
        static $scheme;

        if ($scheme === null) {
            $scheme = new CamelCaseScheme();
        }

        $result = $scheme->convertArrayKeys($output);
        return $result;
    }

    /**
     * Generate pager info when the total number of records is not known.
     *
     * @param int|array|bool $rows The count of rows from the current query or an array of rows from a result.
     * You can also pass **true** to signify there are more pages.
     * @param string $url The basic URL format without querystring.
     * @param array $query The current query string.
     * @param Schema $schema The query string schema.
     * @return array Returns an array suitable to generate a pager.
     */
    public static function morePagerInfo($rows, $url, array $query, Schema $schema) {
        $count = is_array($rows) ? count($rows) : $rows;

        $r = [
            'page' => $query['page'] ?: 1,
            'more' => $count === true || $count >= $query['limit'],
            'urlFormat' => static::pagerUrlFormat($url, $query, $schema)
        ];

        return $r;
    }

    /**
     * Get pager information when the total number of records is known.
     *
     * @param int $totalCount The total number of records.
     * @param string $url The basic URL format without querystring.
     * @param array $query The current query string.
     * @param Schema $schema The query string schema.
     * @return array Returns an array suitable to generate a pager.
     */
    public static function numberedPagerInfo($totalCount, $url, array $query, Schema $schema) {
        $r = [
            'page' => $query['page'] ?: 1,
            'pageCount' => static::pageCount($totalCount, $query['limit']),
            'urlFormat' => static::pagerUrlFormat($url, $query, $schema),
            'totalCount' => $totalCount, // For regenerating with different URL.
        ];
        return $r;
    }

    /**
     * Calculate the page count for a given total record count and records per page.
     *
     * @param int $count The total record count.
     * @param int $limit The number of records per page.
     * @return int Returns the number of pages.
     */
    public static function pageCount($count, $limit) {
        return (int)ceil($count / $limit);
    }

    /**
     * Calculate a pager URL format.
     *
     * This method passes through all of the non-default arguments from a query string to the resulting URL.
     *
     * @param string $url The basic URL format without querystring.
     * @param array $query The current query string.
     * @param Schema $schema The query string schema.
     * @return string Returns a URL with a %s placeholder for page number.
     */
    protected static function pagerUrlFormat($url, array $query, Schema $schema) {
        $properties = $schema->getField('properties', []);

        // Loop through the query and add its parameters to the URL.
        $args = [];
        foreach ($query as $key => $value) {
            if ($key !== 'page' && (!isset($properties[$key]['default']) || $value != $properties[$key]['default'])) {
                $args[$key] = $value;
            }
        }
        $argsStr = http_build_query($args);
        if (strpos($url, '%s') === false) {
            $argsStr = 'page=%s'.(empty($argsStr) ? '' : '&'.$argsStr);
        }
        if (!empty($argsStr)) {
            $url = (strpos($url, '?') === false ? '?' : '&').$argsStr;
        }
        return $url;
    }
}

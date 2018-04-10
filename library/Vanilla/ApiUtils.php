<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\Schema\Schema;
use Garden\Web\Data;
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
     * Get an "expand" parameter definition with specific fields.
     *
     * @param array $fields Valid values for the expand parameter.
     * @param bool|string $default The default value of expand.
     * @return array
     */
    public static function getExpandDefinition(array $fields, $default = false) {
        $result = [
            'description' => 'Expand associated records using one or more valid field names. A boolean true expands all expandable fields.',
            'default' => $default,
            'items' => [
                'enum' => $fields,
                'type' => 'string'
            ],
            'style' => 'form',
            'type' => ['boolean', 'array'],
        ];
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

        return [
            'page' => $query['page'] ?: 1,
            'more' => $count === true || $count >= $query['limit'],
            'urlFormat' => static::pagerUrlFormat($url, $query, $schema),
        ];
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
        return [
            'page' => $query['page'] ?: 1,
            'pageCount' => static::pageCount($totalCount, $query['limit']),
            'urlFormat' => static::pagerUrlFormat($url, $query, $schema),
            'totalCount' => $totalCount, // For regenerating with different URL.
        ];
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
     * Note that the returned URL is not compatible with printf due to URL encoding.
     *
     * @param string $url The basic URL format without querystring.
     * @param array $query The current query string.
     * @param Schema $schema The query string schema.
     * @return string Returns a URL with a %s placeholder for page number.
     */
    private static function pagerUrlFormat($url, array $query, Schema $schema) {
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
            $url .= (strpos($url, '?') === false ? '?' : '&').$argsStr;
        }
        return $url;
    }

    /**
     * Convert query parameters to filters. Useful to fill a where clause ;)
     *
     * @throws \Exception If something goes wrong. Example, the field processor is not callable.
     * @param Schema $schema
     * @param array $query
     * @return array
     */
    public static function queryToFilters(Schema $schema, array $query) {
        $filters = [];
        if (empty($schema['properties'])) {
            return $filters;
        }

        foreach ($schema['properties'] as $property => $data) {
            if (!isset($data['x-filter']) || !array_key_exists($property, $query) || !isset($data['x-filter']['field'])) {
                continue;
            }

            $filterParam = $data['x-filter'];

            // processor($name, $value) => [$updatedName => $updatedValue]
            if (isset($filterParam['processor'])) {
                if (!is_callable($filterParam['processor'])) {
                    throw new \Exception('Field processor is not a callable');
                }
                $filters += $filterParam['processor']($filterParam['field'], $query[$property]);
            } else {
                $filters += [$filterParam['field'] => $query[$property]];
            }

        }

        return $filters;
    }
}

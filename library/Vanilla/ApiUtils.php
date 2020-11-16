<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\CapitalCaseScheme;
use Vanilla\Utility\ModelUtils;

/**
 * Utility methods useful for greating API endpoints.
 */
class ApiUtils {
    public const DEFAULT_LIMIT = 30;

    /**
     * Expand field value to indicate expanding all fields.
     *
     * @deprecated
     */
    const EXPAND_ALL = \Vanilla\Utility\ModelUtils::EXPAND_ALL;

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
     * @return Schema
     */
    public static function getExpandDefinition(array $fields, $default = null) {
        if (!in_array(ModelUtils::EXPAND_ALL, $fields)) {
            $fields[] = ModelUtils::EXPAND_ALL;
            $fields[] = ModelUtils::EXPAND_CRAWL;
        }

        $negativeKeys = array_filter($fields, function ($enumVal) {
            return stringBeginsWith($enumVal, '-');
        });

        $negativeKeysStripped = array_map(function ($enumVal) {
            return str_replace('-', '', $enumVal);
        }, $negativeKeys);

        $enumVals = array_unique(array_merge($fields, $negativeKeysStripped));
        $default = !empty($negativeKeysStripped) ? array_values($negativeKeysStripped) : false;

        $schema = new Schema([
            'description' =>
                'Expand associated records using one or more valid field names. A value of "'
                . ModelUtils::EXPAND_ALL
                . '" will expand all expandable fields.',
            'default' => $default,
            'items' => [
                'enum' => $enumVals,
                'type' => 'string'
            ],
            'nullable' => true,
            'style' => 'form',
            'type' => ['boolean', 'array'],
        ]);

        $schema->addFilter('', function ($value) use ($negativeKeys) {
            if (!is_array($value)) {
                return $value;
            }

            foreach ($negativeKeys as $negativeKey) {
                $negativeKeyStripped = str_replace('-', '', $negativeKey);
                if (!in_array($negativeKey, $value) && !in_array($negativeKeyStripped, $value)) {
                    // Add it in as a default value if it wasn't excluded.
                    $value[] = $negativeKeyStripped;
                }
            }

            return array_values($value);
        });
        return $schema;
    }

    /**
     * Get the maximum limit for the API.
     *
     * @param int $default The default value to use.
     *
     * @return int
     */
    public static function getMaxLimit(int $default = 500): int {
        return \Gdn::config('APIv2.MaxLimit', $default);
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
            'limit' => $query['limit'],
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
                if (is_object($value) && method_exists($value, "__toString")) {
                    // If we can stringify, do it.
                    $args[$key] = (string)$value;
                } else {
                    $args[$key] = $value;
                }
            }
        }
        $argsStr = http_build_query($args);
        if (strpos($url, '%s') === false) {
            $argsStr = 'page=%s'.(empty($argsStr) ? '' : '&'.$argsStr);
        }
        if (!empty($argsStr)) {
            $url .= (strpos($url, '?') === false ? '?' : '&').$argsStr;
        }
        $url = \Gdn::request()->getSimpleUrl($url);
        return $url;
    }

    /**
     * Convert query parameters to filters. Useful to fill a where clause ;)
     *
     * @param Schema $schema
     * @param array $query
     * @return array
     * @throws \Exception If something goes wrong. Example, the field processor is not callable.
     */
    public static function queryToFilters(Schema $schema, array $query) {
        $filters = [];
        if (empty($schema['properties'])) {
            return $filters;
        }

        foreach ($schema['properties'] as $property => $data) {
            if (!isset($data['x-filter']) || !array_key_exists($property, $query)) {
                continue;
            }

            $filterParam = $data['x-filter'];
            if ($filterParam === true) {
                $filterParam = ['field' => $property];
            }

            if (!isset($filterParam['field'])) {
                continue;
            }

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

    /**
     * Parse a `Link` header into a list of page links.
     *
     * @param string $link The link header to parse.
     * @return array|null Returns an array in the form `[rel => url]` or **null** if the header is malformed.
     */
    public static function parsePageHeader(string $link): ?array {
        if (preg_match_all('`<([^>]+)>;\s*rel="([^"]+)"`', $link, $m, PREG_SET_ORDER)) {
            $result = [];
            foreach ($m as $r) {
                $result[$r[2]] = $r[1];
            }
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Takes an array of fields for a sort and adds their descending counterparts.
     *
     * @param string $fields The default sort fields.
     * @return array
     * @throws \InvalidArgumentException Throws an exception if you pass a field that begins with a dash.
     */
    public static function sortEnum(string ...$fields): array {
        $desc = $fields;
        foreach ($desc as &$field) {
            if ($field[0] === '-') {
                throw new \InvalidArgumentException("Default sort fields cannot begin with '-': $field", 400);
            }
            $field = '-'.$field;
        }
        return array_merge($fields, $desc);
    }

    /**
     * Get the database offset/limit from the querystring.
     *
     * This helper supports a query string with the following keys:
     *
     * - limit: Required.
     * - offset: Optional.
     * - page: Optional.
     *
     * @param array $query
     * @return array
     */
    public static function offsetLimit(array $query): array {
        $limit = $query['limit'];
        if (isset($query['offset'])) {
            $offset = $query['offset'];
        } else {
            $offset = $limit * (($query['page'] ?? 1) - 1);
        }
        return [$offset, $limit];
    }
}

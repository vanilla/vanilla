<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;

abstract class AbstractApiController extends \Vanilla\Web\Controller {

    /** @var Schema */
    private $userFragmentSchema;

    /** @var Schema */
    private $postFragmentSchema;

    /**
     * If the parameter value is a valid date filter value, return an array of query conditions.
     *
     * @param string $param The name of the parameter in the request (e.g. dateInserted).
     * @param array $data Request data, such as the query.
     * @param string $field A column name override. If none is provided, the value for $param is used.
     * @return array|bool
     */
    public function dateFilterField($param, array $data, $field = null) {
        if ($field === null) {
            $field = $param;
        }
        $validOperators = ['=', '>', '<', '>=', '<=', '[]', '()', '[)', '(]'];
        $result = false;

        if (array_key_exists($param, $data)) {
            $value = $data[$param];
            if (array_key_exists('op', $value) && array_key_exists('value', $value)) {
                $op = $value['op'];
                $value = $value['value'];

                if (in_array($op, $validOperators)) {
                    switch ($op) {
                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                            if ($value instanceof DateTimeImmutable) {
                                $result = ["{$field} {$op}" => $value];
                            }
                            break;
                        case '=':
                        case '[]':
                        case '()':
                        case '[)':
                        case '(]':
                            // DateFilterSchema has already taken care of any inclusive/exclusive range adjustments.
                            if (is_array($value) && count($value) == 2) {
                                $result = [
                                    "{$field} >=" => $value[0],
                                    "{$field} <=" => $value[1],
                                ];
                            }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Filter unwanted values from an array (particularly empty values from request parameters).
     *
     * @param array $values
     * @return array
     */
    public function filterValues(array $values) {
        $result = array_filter($values, function($val) {
            $valid = true;
            if ($val === '') {
                $valid = false;
            }
            return $valid;
        });
        return $result;
    }

    /**
     * Format a specific field.
     *
     * @param array $row An array representing a database row.
     * @param string $field The field name.
     * @param string $format The source format.
     */
    public function formatField(array &$row, $field, $format) {
        if (array_key_exists($field, $row)) {
            $row[$field] = Gdn_Format::to($row[$field], $format) ?: '<!-- empty -->';
        }
    }

    /**
     * Get an "expand" parameter definition with specific fields.
     *
     * @param array $fields Valid values for the expand parameter.
     * @param bool|string $default The default value of expand.
     * @return array
     */
    public function getExpandDefinition(array $fields, $default = false) {
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
     * Get the schema for users joined to records.
     *
     * @return Schema Returns a schema.
     */
    public function getUserFragmentSchema() {
        if ($this->userFragmentSchema === null) {
            $this->userFragmentSchema = $this->schema([
                'userID:i' => 'The ID of the user.',
                'name:s' => 'The username of the user.',
                'photoUrl:s' => 'The URL of the user\'s avatar picture.'
            ], 'UserFragment');
        }
        return $this->userFragmentSchema;
    }

    /**
     * Get the schema for posts joined to records.
     *
     * Posts are joined to categories and discussions, usually in the form of **firstPost** and **lastPost** fields.
     *
     * @return Schema Returns a schema.
     */
    public function getPostFragmentSchema() {
        if ($this->postFragmentSchema === null) {
            $this->postFragmentSchema = $this->schema([
                'discussionID:i?' => 'The discussion ID of the post.',
                'commentID:i?' => 'The comment ID of the post, if any.',
                'name:s' => 'The title of the post.',
                'url:s' => 'The URL of the post.',
                'dateInserted:dt' => 'The date of the post.',
                'insertUserID:i' => 'The author of the post.',
                'insertUser?' => $this->getUserFragmentSchema(),
            ], 'PostFragment');
        }
        return $this->postFragmentSchema;
    }

    /**
     * Determine if a value is in the "expand" parameter.
     *
     * @param string $field The field name to search for.
     * @param array|bool $expand An array of fields to expand, or true for all.
     * @return bool
     */
    public function isExpandField($field, $expand) {
        $result = false;
        if ($expand === true) {
            // A boolean true allows everything.
            $result = true;
        } elseif (is_array($expand)) {
            $result = in_array($field, $expand);
        }
        return $result;
    }

    public function options($path) {
        return '';
    }

    /**
     * Resolve values from an expand parameter, based on the provided map.
     *
     * @param array $request An array representing request data.
     * @param array $map An array of short-to-full field names (e.g. insertUser => InsertUserID).
     * @param string $field The name of the field where the expand fields can be found.
     * @return array
     */
    protected function resolveExpandFields(array $request, array $map, $field = 'expand') {
        $result = [];
        if (array_key_exists($field, $request)) {
            $expand = $request[$field];
            if ($expand === true) {
                // If the expand parameter is true, expand everything.
                $result = array_values($map);
            } elseif (is_array($expand)) {
                foreach ($map as $short => $full) {
                    if ($this->isExpandField($short, $expand)) {
                        $result[] = $full;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Verify current user permission, if a particular field is in a data array.
     *
     * @param array $data The data array (e.g. request body fields).
     * @param string $field The protected field name.
     * @param string|array $permission A required permissions.
     * @param int|null $id The ID of the record we are checking the permission of (e.g. category ID).
     */
    public function fieldPermission(array $data, $field, $permission, $id = null) {
        if (array_key_exists($field, $data)) {
            $this->permission($permission, $id);
        }
    }
}

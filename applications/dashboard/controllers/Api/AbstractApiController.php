<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;

abstract class AbstractApiController extends \Vanilla\Web\Controller {
    /**
     * @var Schema
     */
    private $userFragmentSchema;

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

    public function options($path) {
        return '';
    }
}

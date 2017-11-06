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
     * @var Schema
     */
    private $postFragmentSchema;

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

    public function options($path) {
        return '';
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

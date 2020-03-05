<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Community\Schemas\CategoryFragmentSchema;
use Vanilla\Community\Schemas\PostFragmentSchema;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Utility\ModelUtils;

/**
 * Base API controller for APIv2.
 */
abstract class AbstractApiController extends \Vanilla\Web\Controller implements \Vanilla\InjectableInterface {

    use FormatFieldTrait;

    /** @var Schema */
    private $categoryFragmentSchema;

    /** @var Schema */
    private $userFragmentSchema;

    /** @var Schema */
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
     * Get the schema for categories joined to records.
     *
     * @return Schema Returns a schema.
     */
    public function getCategoryFragmentSchema() {
        if ($this->categoryFragmentSchema === null) {
            $this->categoryFragmentSchema = $this->schema(new CategoryFragmentSchema(), 'CategoryFragment');
        }
        return $this->categoryFragmentSchema;
    }

    /**
     * Get the schema for users joined to records.
     *
     * @return Schema Returns a schema.
     */
    public function getUserFragmentSchema() {
        if ($this->userFragmentSchema === null) {
            $this->userFragmentSchema = $this->schema(new \Vanilla\Models\UserFragmentSchema(), 'UserFragment');
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
            $this->postFragmentSchema = $this->schema(new PostFragmentSchema(), 'PostFragment');
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
        $result = ModelUtils::isExpandOption($field, $expand);
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
     * @return array Returns an array of field names that were expanded from the `$map` argument.
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

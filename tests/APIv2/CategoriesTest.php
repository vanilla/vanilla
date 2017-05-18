<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoriesTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/categories';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Delete a category.
     *
     * @requires function CategoriesApiController::delete
     */
    public function testDelete() {
        $this->fail('Remove placeholder for CategoriesApiController::delete test');
    }

    /**
     * Get a category.
     *
     * @requires function CategoriesApiController::get
     */
    public function testGet() {
        $this->fail('Remove placeholder for CategoriesApiController::get test');
    }

    /**
     * Get a category's data, prepared for editing.
     *
     * @requires function CategoriesApiController::get_edit
     */
    public function testGetEdit() {
        $this->fail('Remove placeholder for CategoriesApiController::get_edit test');
    }

    /**
     * Get a category's data, prepared for editing.
     *
     * @requires function CategoriesApiController::get_edit
     */
    public function testGetEditFields() {
        $this->fail('Remove placeholder for CategoriesApiController::get_edit test');
    }

    /**
     * Get a list of categories.
     *
     * @requires function CategoriesApiController::post
     */
    public function testIndex() {
        $this->fail('Remove placeholder for CategoriesApiController::index test');
    }

    /**
     * Search categories.
     *
     * @requires function CategoriesApiController::post
     */
    public function testGetSearch() {
        $this->fail('Remove placeholder for CategoriesApiController::get_search test');
    }

    /**
     * Update a category row.
     *
     * @requires function CategoriesApiController::patch
     */
    public function testPatchFull() {
        $this->fail('Remove placeholder for CategoriesApiController::patch full test');
    }

    /**
     * Update a category row.
     *
     * @param string $field The name of the field to patch.
     * @requires function CategoriesApiController::patch
     */
    public function testPatchSparse($field) {
        $this->fail('Remove placeholder for CategoriesApiController::patch sparse test');
    }

    /**
     * Insert a category.
     *
     * @requires function CategoriesApiController::post
     */
    public function testPost() {
        $this->fail('Remove placeholder for CategoriesApiController::post test');
    }
}

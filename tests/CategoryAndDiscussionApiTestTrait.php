<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests;

use Gdn;

/**
 * Class CategoryAndDiscussionApiTestTrait
 *
 * @package VanillaTests
 */
trait CategoryAndDiscussionApiTestTrait {

    /**
     * @var $lastInsertedCategoryID
     */
    private $lastInsertedCategoryID;

    /**
     * @var $lastInsertedDiscussionID
     */
    private $lastInsertedDiscussionID;

    /**
     * Clear local info between tests.
     */
    public function setUpCategoryAndDiscussionApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedDiscussionID = null;
    }

    /**
     * Create a test category.
     *
     * @param array $overrides
     * @return array
     */
    public function createCategory(array $overrides = []): array {
        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController =  Gdn::getContainer()->get('CategoriesApiController');

        $categoryInfo = uniqid('category_');
        $body = $overrides +
            [
                'name' => $categoryInfo,
                'urlCode' =>  $categoryInfo,
            ];

        $category = $categoriesAPIController->post($body);

        $this->lastInsertedCategoryID = $category['categoryID'];
        return $category;
    }

    /**
     * Create a Discussion.
     *
     * @param array $overrides
     * @return array
     */
    public function createDiscussion(array $overrides = []): array {
        /** @var \DiscussionsApiController $discussionAPIController */
        $discussionAPIController =  Gdn::getContainer()->get('DiscussionsApiController');
        $categoryID = $body['categoryID'] ?? $this->lastInsertedCategoryID;
        if (!$categoryID) {
            $category = $this->createCategory();
            $categoryID = $category['categoryID'];
        }

        $body = $overrides +
            [
                'categoryID' => $categoryID,
                'type' => null,
                'name' => 'Discussion',
                'body' => 'Discussion',
                'format' => 'markdown',

            ];

        $discussion = $discussionAPIController->post($body);
        $this->lastInsertedDiscussionID = $discussion['discussionID'];

        return $discussion;
    }

    /**
     * Reset necessary tables.
     *
     * @param string $name
     */
    protected function resetTable(string $name): void {
        Gdn::database()->sql()->truncate($name);
    }
}

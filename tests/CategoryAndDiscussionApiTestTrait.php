<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests;

use Gdn;
use TagModel;

/**
 * Class CategoryAndDiscussionApiTestTrait
 *
 * @package VanillaTests
 */
trait CategoryAndDiscussionApiTestTrait {

    /**
     * @var int|null $lastInsertedCategoryID
     */
    protected $lastInsertedCategoryID;

    /**
     * @var int|null $lastInsertedDiscussionID
     */
    protected $lastInsertedDiscussionID;

    /**
     * Clear local info between tests.
     */
    public function setUpCategoryAndDiscussionApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedDiscussionID = null;
        \DiscussionModel::cleanForTests();
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
        $categoryID = $overrides['categoryID'] ?? $this->lastInsertedCategoryID;
        if (!$categoryID) {
            $category = $this->createCategory();
            $categoryID = $category['categoryID'];
        }

        $body = VanillaTestCase::sprintfCounter($overrides +
            [
                'categoryID' => $categoryID,
                'type' => null,
                'name' => 'Discussion %s',
                'body' => 'Discussion %s',
                'format' => 'markdown',

            ]);

        $discussion = $discussionAPIController->post($body);
        $this->lastInsertedDiscussionID = $discussion['discussionID'];

        return $discussion;
    }

    /**
     * Create Tags.
     *
     * @param array $overrides
     * @return array
     */
    public function createTag(array $overrides = []): array {
        /** @var TagModel $tagModel */
        $tagModel = Gdn::getContainer()->get(TagModel::class);

        $name = $overrides['name'] ?? uniqid('tagName_');
        $fullName = $overrides['fullName'] ?? $name;
        $type = $overrides['type'] ?? '';

        $tag = $tagModel->save([
            'Name' => $name,
            'FullName' => $fullName,
            'Type' => $type
        ]);

        if (!is_array($tag)) {
            $tag = $tagModel->getWhere(["Name" => $name])->firstRow(DATASET_TYPE_ARRAY);
        }

        return $tag;
    }
}

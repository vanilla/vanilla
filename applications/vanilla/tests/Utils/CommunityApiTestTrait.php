<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Utils;

use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Http\InternalClient;

/**
 * @method InternalClient api()
 */
trait CommunityApiTestTrait {

    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /** @var int|null */
    protected $lastInsertedDiscussionID = null;

    /** @var int|null */
    protected $lastInsertCommentID = null;

    /**
     * Clear local info between tests.
     */
    public function setUpCommunityApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedDiscussionID = null;
        $this->lastInsertCommentID = null;
    }

    /**
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createCategory(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $name = "Test Category $salt";
        $categoryID = $overrides['parentCategoryID'] ?? $this->lastInsertedCategoryID;

        $params = $overrides + [
                'customPermissions' => false,
                'displayAs' => 'discussions',
                'parentCategoryID' => $categoryID,
                'name' => $name,
                'urlCode' => slugify($name),
                'featured' => false
            ];
        $result = $this->api()->post('/categories', $params)->getBody();
        $this->lastInsertedCategoryID = $result['categoryID'];
        return $result;
    }

    /**
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     * @param int[] $viewRoleIDs
     *
     * @return array
     */
    public function createPermissionedCategory(array $overrides = [], array $viewRoleIDs = []): array {
        $result = $this->createCategory(['customPermissions' => true] + $overrides);

        foreach ($viewRoleIDs as $viewRoleID) {
            // Make the cateogry and it's contents hidden to guests.
            $this->api()->patch("/roles/$viewRoleID", [
                'permissions' => [[
                    "id" => $this->lastInsertedCategoryID,
                    'type' => "category",
                    "permissions" =>  [
                        "discussions.view" => true
                    ],
                ]],
            ]);
        }

        return $result;
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createDiscussion(array $overrides = []): array {
        $categoryID = $overrides['categoryID'] ?? $this->lastInsertedCategoryID ?? -1;

        if ($categoryID === null) {
            throw new \Exception('Could not insert a test discussion because no category was specified.');
        }

        $params = $overrides + [
            'name' => 'Test Discussion',
            'format' => TextFormat::FORMAT_KEY,
            'body' => 'Hello Discussion',
            'categoryID' => $categoryID,
        ];
        $result = $this->api()->post('/discussions', $params)->getBody();
        $this->lastInsertedDiscussionID = $result['discussionID'];

        if (isset($overrides['score'])) {
            $this->setDiscussionScore($this->lastInsertedDiscussionID, $overrides['score']);
        }
        return $result;
    }

    /**
     * Give score to a discussion.
     *
     * @param int $discussionID
     * @param int $score
     */
    public function setDiscussionScore(int $discussionID, int $score) {
        /** @var \DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
        $discussionModel->setField($discussionID, 'Score', $score);
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createComment(array $overrides = []): array {
        $discussionID = $overrides['discussionID'] ?? $this->lastInsertedDiscussionID;

        if ($discussionID === null) {
            throw new \Exception('Could not insert a test comment because no discussion was specified.');
        }

        $params = $overrides + [
            'format' => TextFormat::FORMAT_KEY,
            'body' => 'Hello Comment',
            'discussionID' => $discussionID,
        ];
        $result = $this->api()->post('/comments', $params)->getBody();
        $this->lastInsertCommentID = $result['commentID'];
        if (isset($overrides['score'])) {
            $this->setCommentScore($this->lastInsertCommentID, $overrides['score']);
        }
        return $result;
    }

    /**
     * Give score to a discussion.
     *
     * @param int $commentID
     * @param int $score
     */
    public function setCommentScore(int $commentID, int $score) {
        /** @var \CommentModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(\CommentModel::class);
        $discussionModel->setField($commentID, 'Score', $score);
    }

    /**
     * Sort categories.
     *
     * @param array $categoryData Mapping of parentID => childIDs in order.
     * @example
     * [
     *     -1 => [1, 4, 6],
     *     1 => [8, 9],
     * ]
     */
    public function sortCategories(array $categoryData) {
        foreach ($categoryData as $parentID => $childKeys) {
            $query = [
                'ParentID' => $parentID,
                'Subtree' => json_encode(array_map(function ($childID) {
                    return ['CategoryID' => $childID];
                }, $childKeys)),
            ];
            $this->bessy()->post("/vanilla/settings/categoriestree.json", $query);
        }
    }
}

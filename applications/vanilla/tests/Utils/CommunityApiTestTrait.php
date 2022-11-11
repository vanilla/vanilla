<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Utils;

use Garden\Http\HttpResponse;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Http\InternalClient;
use Vanilla\Utility\ModelUtils;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\VanillaTestCase;

/**
 * @method InternalClient api()
 */
trait CommunityApiTestTrait
{
    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /** @var int|null */
    protected $lastInsertedDiscussionID = null;

    /** @var int|null */
    protected $lastInsertCommentID = null;

    /** @var int|null */
    protected $lastInsertedTagID = null;

    /** @var int|null */
    protected $lastInsertedCollectionID = null;

    /** @var HttpResponse|null */
    protected $lastCommunityResponse;

    /**
     * Clear local info between tests.
     */
    public function setUpCommunityApiTestTrait(): void
    {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedDiscussionID = null;
        $this->lastInsertCommentID = null;
        $this->lastInsertedTagID = null;
        $this->lastInsertedCollectionID = null;
        $this->lastResponse = null;
    }

    /**
     * Reset the categories table, leaving the root category alone.
     */
    public function resetCategoryTable()
    {
        $model = new \Gdn_Model("Category");
        $model->delete(["CategoryID >" => 0]);
        \CategoryModel::clearCache();
    }

    /**
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     * @param array $extras Extra category fields.
     *
     * @return array
     */
    public function createCategory(array $overrides = [], array $extras = []): array
    {
        $salt = "-" . round(microtime(true) * 1000) . rand(1, 1000);
        $name = "Test Category $salt";
        $categoryID = $overrides["parentCategoryID"] ?? $this->lastInsertedCategoryID;

        $params = $overrides + [
            "customPermissions" => false,
            "displayAs" => "discussions",
            "parentCategoryID" => $categoryID,
            "name" => $name,
            "urlCode" => slugify($name),
            "featured" => false,
        ];
        $this->lastCommunityResponse = $this->api()->post("/categories", $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertedCategoryID = $result["categoryID"];
        $categoryModel = \CategoryModel::instance();
        if (!empty($extras)) {
            $categoryModel->setField($this->lastInsertedCategoryID, $extras);
        }
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
    public function createPermissionedCategory(array $overrides = [], array $viewRoleIDs = []): array
    {
        $result = $this->createCategory(["customPermissions" => true] + $overrides);

        foreach ($viewRoleIDs as $viewRoleID) {
            // Make the category and it's contents hidden to guests.
            $this->api()->patch("/roles/$viewRoleID", [
                "permissions" => [
                    [
                        "id" => $this->lastInsertedCategoryID,
                        "type" => "category",
                        "permissions" => [
                            "discussions.view" => true,
                        ],
                    ],
                ],
            ]);
        }

        return $result;
    }

    /**
     * Create a collection.
     *
     * @param array $records Records to insert into the collection.
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createCollection(array $records = [], array $overrides = []): array
    {
        $params = $overrides + [
            "name" => "Test Collection",
            "records" => $records,
        ];

        $apiUrl = "/collections";
        $this->lastCommunityResponse = $this->api()->post($apiUrl, $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertedCollectionID = $result["collectionID"] ?? null;

        return $result;
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     * @param array $extras Extra fields to set directly in the model.
     *
     * @return array
     */
    public function createDiscussion(array $overrides = [], array $extras = []): array
    {
        $categoryID = $overrides["categoryID"] ?? ($this->lastInsertedCategoryID ?? -1);

        if ($categoryID === null) {
            throw new \RuntimeException("Could not insert a test discussion because no category was specified.");
        }

        $params = $overrides + [
            "name" => "Test Discussion",
            "format" => TextFormat::FORMAT_KEY,
            "body" => "Hello Discussion",
            "categoryID" => $categoryID,
        ];

        $type = $overrides["type"] ?? "discussion";
        $apiUrl = "/discussions";
        if ($type !== "discussion") {
            $apiUrl .= "/" . strtolower($type);
        }

        $this->lastCommunityResponse = $this->api()->post($apiUrl, $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertedDiscussionID = $result["discussionID"] ?? null;
        if ($this->lastInsertedDiscussionID === null) {
            return $result;
        }

        if (isset($overrides["score"])) {
            $this->setDiscussionScore($this->lastInsertedDiscussionID, $overrides["score"]);
        }

        if (!empty($extras)) {
            /** @var \DiscussionModel $discussionModel */
            $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
            $discussionModel->setField($this->lastInsertedDiscussionID, $extras);
            ModelUtils::validationResultToValidationException($discussionModel);
        }
        return $result;
    }

    /**
     * Create Tag.
     *
     * @param array $overrides
     * @return array
     */
    public function createTag(array $overrides = []): array
    {
        $tagName = $overrides["name"] ?? "tagName_" . VanillaTestCase::id("tagName");
        $params = $overrides + [
            "name" => $tagName,
            "fullName" => $tagName,
            "type" => "",
        ];

        $apiUrl = "/tags";

        $this->lastCommunityResponse = $this->api()->post($apiUrl, $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertedTagID = $result["tagID"] ?? null;
        if ($this->lastInsertedTagID === null) {
            return $result;
        }
        return $result;
    }

    /**
     * Bookmark a discussion for the current user.
     *
     * @param int|null $discussionID
     */
    public function bookmarkDiscussion(?int $discussionID = null)
    {
        $discussionID = $discussionID ?? $this->lastInsertedDiscussionID;
        if ($discussionID === null) {
            throw new \Exception("Specify a discussion to bookmark.");
        }
        $this->api()->put("/discussions/$discussionID/bookmark", ["bookmarked" => true]);
    }

    /**
     * Give score to a discussion.
     *
     * @param int $discussionID
     * @param int $score
     */
    public function setDiscussionScore(int $discussionID, int $score)
    {
        /** @var \DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
        $discussionModel->setField($discussionID, "Score", $score);
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createComment(array $overrides = []): array
    {
        $discussionID = $overrides["discussionID"] ?? $this->lastInsertedDiscussionID;

        if ($discussionID === null) {
            throw new \Exception("Could not insert a test comment because no discussion was specified.");
        }

        $params = $overrides + [
            "format" => TextFormat::FORMAT_KEY,
            "body" => "Hello Comment",
            "discussionID" => $discussionID,
        ];
        $this->lastCommunityResponse = $this->api()->post("/comments", $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertCommentID = $result["commentID"] ?? null;
        if ($this->lastInsertCommentID === null) {
            return $result;
        }
        if (isset($overrides["score"])) {
            $this->setCommentScore($this->lastInsertCommentID, $overrides["score"]);
        }
        return $result;
    }

    /**
     * Give score to a discussion.
     *
     * @param int $commentID
     * @param int $score
     */
    public function setCommentScore(int $commentID, int $score)
    {
        /** @var \CommentModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(\CommentModel::class);
        $discussionModel->setField($commentID, "Score", $score);
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
    public function sortCategories(array $categoryData)
    {
        foreach ($categoryData as $parentID => $childKeys) {
            $query = [
                "ParentID" => $parentID,
                "Subtree" => json_encode(
                    array_map(function ($childID) {
                        return ["CategoryID" => $childID];
                    }, $childKeys)
                ),
            ];
            $this->bessy()->post("/vanilla/settings/categoriestree.json", $query);
        }
    }

    /**
     * Create a media item.
     *
     * @param array $overrides
     * @param array $attachmentData
     * @return mixed|string
     * @throws \Exception If mediaID is null.
     */
    public function createMedia(array $overrides = [], array $attachmentData = [])
    {
        TestUploader::resetUploads();
        $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");

        $params = $overrides + [
            "file" => $photo,
            "type" => "image",
        ];
        $response = $this->api()->post("/media", $params);
        $result = $response->getBody();

        $mediaID = $result["mediaID"] ?? null;

        if (is_null($mediaID)) {
            throw new \Exception("Could not insert a test media because mediaID is null");
        }

        // Update media with $attachmentData
        $response = $this->api()->patch("/media/{$mediaID}/attachment", $attachmentData);
        return $response->getBody();
    }

    /**
     * Have a user follow a category
     *
     * @param array|int $userOrUserID A user or userID.
     * @param array $categoryOrCategoryID A category or categoryID.
     * @param string $mode One of the CategoryModel::NOTIFICATION_* constants.
     * @return void
     */
    public function setCategoryPreference($userOrUserID, $categoryOrCategoryID, string $mode)
    {
        $this->runWithUser(function () use ($categoryOrCategoryID, $mode, $userOrUserID) {
            $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
            $categoryID = is_array($categoryOrCategoryID) ? $categoryOrCategoryID["categoryID"] : $categoryOrCategoryID;
            $this->api()->patch("/categories/$categoryID/preferences/$userID", [
                \CategoryModel::PREFERENCE_KEY_NOTIFICATION => $mode,
            ]);
        }, $userOrUserID);
    }
}

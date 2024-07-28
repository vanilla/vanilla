<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Utils;

use AttachmentModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Http\HttpResponse;
use Garden\Schema\ValidationException;
use Gdn_Format;
use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
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

    /** @var int|null */
    protected $lastReportID = null;

    /** @var int|null */
    protected $lastEscalationID = null;

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
        $this->lastReportID = null;
        $this->lastEscalationID = null;
    }

    /**
     * Create a report.
     *
     * @param array $post The post to report. (Result from {@link self::createDiscussion()} or {@link self::createComment()})
     * @param array $overrides Fields to override on the insert.
     *
     * @return array The report.
     */
    public function createReport(array $post, array $overrides = []): array
    {
        if (isset($post["commentID"])) {
            $recordType = "comment";
            $recordID = $post["commentID"];
        } elseif (isset($post["discussionID"])) {
            $recordType = "discussion";
            $recordID = $post["discussionID"];
        } else {
            TestCase::fail("Could not determine recordType and recordID from post data.");
        }

        if (isset($overrides["reportReasonIDs"])) {
            // In case someone passed a full reason array.
            $overrides["reportReasonIDs"] = array_map(function (mixed $reportReasonID) {
                if (is_array($reportReasonID) && isset($reportReasonID["reportReasonID"])) {
                    return $reportReasonID["reportReasonID"];
                } else {
                    return $reportReasonID;
                }
            }, $overrides["reportReasonIDs"]);
        }

        $defaults = [
            "recordType" => $recordType,
            "recordID" => $recordID,
            "reportReasonIDs" => [ReportReasonModel::INITIAL_REASON_ABUSE],
            "noteBody" => "This is a test report.",
            "noteFormat" => "markdown",
        ];

        $params = $overrides + $defaults;
        $response = $this->api()->post("/reports", $params);
        TestCase::assertEquals(201, $response->getStatusCode());
        $report = $response->getBody();
        $this->lastReportID = $report["reportID"];
        return $report;
    }

    /**
     * Create a report reason and return it.
     *
     * @param array $overrides
     *
     * @return array
     */
    public function createReportReason(array $overrides = []): array
    {
        $name = "customReason" . VanillaTestCase::id("reason");

        $defaults = [
            "reportReasonID" => $name,
            "name" => $name,
            "description" => "This is a description of reason {$name}",
        ];

        $body = $overrides + $defaults;

        $response = $this->api()->post("/report-reasons", $body);
        TestCase::assertEquals(201, $response->getStatusCode());

        return $response->getBody();
    }

    /**
     * Create an escalation. Will create a report automatically if one is not provided.
     *
     * @param array $post The post to report. (Result from {@link self::createDiscussion()} or {@link self::createComment()})
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createEscalation(array $post, array $overrides = []): array
    {
        if (isset($post["commentID"])) {
            $recordType = "comment";
            $recordID = $post["commentID"];
        } elseif (isset($post["discussionID"])) {
            $recordType = "discussion";
            $recordID = $post["discussionID"];
        } else {
            TestCase::fail("Could not determine recordType and recordID from post data.");
        }

        $defaults = [
            "recordType" => $recordType,
            "recordID" => $recordID,
            "name" => "This is an escalation",
        ];

        if (isset($overrides["reportID"])) {
            // We have a report.
        } else {
            // Add in an on the fly report.
            $dummyReport = [
                "reportReasonIDs" => [ReportReasonModel::INITIAL_REASON_ABUSE],
                "noteBody" => "This is a test report for an escalation.",
                "noteFormat" => "markdown",
            ];
            $defaults += $dummyReport;
        }

        $params = $overrides + $defaults;
        $response = $this->api()->post("/escalations", $params);
        TestCase::assertEquals(201, $response->getStatusCode());
        $escalation = $response->getBody();
        $this->lastEscalationID = $escalation["escalationID"];
        return $escalation;
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
     * @param array $permissionRoleIDMapping
     *
     * @return array
     */
    public function createPermissionedCategory(
        array $overrides = [],
        array $viewRoleIDs = [],
        array $permissionRoleIDMapping = []
    ): array {
        $result = $this->createCategory(["customPermissions" => true] + $overrides);

        $permissionRoleIDMapping["discussions.view"] = $viewRoleIDs;

        $allRoleIDs = array_merge(...array_values($permissionRoleIDMapping));
        foreach ($allRoleIDs as $roleID) {
            $permissions = [];
            foreach ($permissionRoleIDMapping as $permission => $roleIDs) {
                $permissions[$permission] = in_array($roleID, $roleIDs);
            }

            $this->api()->patch("/roles/$roleID", [
                "permissions" => [
                    [
                        "id" => $this->lastInsertedCategoryID,
                        "type" => "category",
                        "permissions" => $permissions,
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
            "name" => "Test Collection" . $this->generateCollectionSalt(),
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
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function createDiscussion(array $overrides = [], array $extras = []): array
    {
        $categoryID = $overrides["categoryID"] ?? ($this->lastInsertedCategoryID ?? -1);

        $expands = $extras["expand"] ?? null;
        unset($extras["expand"]);

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

        $needsRefetch = $expands !== null;
        if (isset($overrides["score"])) {
            $this->setDiscussionScore($this->lastInsertedDiscussionID, $overrides["score"]);
            $needsRefetch = true;
        }

        if (!empty($extras)) {
            /** @var \DiscussionModel $discussionModel */
            $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
            $discussionModel->setField($this->lastInsertedDiscussionID, $extras);
            ModelUtils::validationResultToValidationException($discussionModel);
            $needsRefetch = true;
        }
        // Fetch again so we can handle the added extras
        if ($needsRefetch) {
            $result = $this->api()
                ->get("/discussions/{$result["discussionID"]}", ["expand" => $expands])
                ->getBody();
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
            throw new Exception("Specify a discussion to bookmark.");
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
     * Create a comment.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     * @throws Exception
     */
    public function createComment(array $overrides = []): array
    {
        $discussionID = $overrides["discussionID"] ?? $this->lastInsertedDiscussionID;

        if ($discussionID === null) {
            throw new Exception("Could not insert a test comment because no discussion was specified.");
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
     * @throws Exception If mediaID is null.
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
            throw new Exception("Could not insert a test media because mediaID is null");
        }

        // Update media with $attachmentData
        $response = $this->api()->patch("/media/{$mediaID}/attachment", $attachmentData);
        return $response->getBody();
    }

    /**
     * Mock an attachment on a post.
     *
     * @param string $recordType
     * @param int $recordID
     * @return array|object
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function createAttachment(string $recordType, int $recordID)
    {
        $recordType = strtolower($recordType);
        $recordPrefix = substr($recordType, 0, 1);
        $attachment = [
            "Type" => "mock-issue",
            "ForeignID" => "{$recordPrefix}-{$recordID}",
            "ForeignUserID" => self::$siteInfo["adminUserID"],
            "Source" => "mockSite",
            "SourceID" => 99,
            "SourceURL" => "https://www.example.com",
            "Status" => "active",
            "LastModifiedDate" => Gdn_Format::toDateTime(),
        ];

        $attachmentModel = $this->container()->get(AttachmentModel::class);
        $id = $attachmentModel->save($attachment);
        $savedAttachment = $attachmentModel->getID($id);
        return $savedAttachment;
    }

    /**
     * Have a user follow a category
     *
     * @param array|int $userOrUserID A user or userID.
     * @param array $categoryOrCategoryID A category or categoryID.
     * @param array $preferences An array of category notification preferences.
     * @return void
     */
    public function setCategoryPreference($userOrUserID, $categoryOrCategoryID, array $preferences)
    {
        $this->runWithUser(function () use ($categoryOrCategoryID, $preferences, $userOrUserID) {
            $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
            $categoryID = is_array($categoryOrCategoryID) ? $categoryOrCategoryID["categoryID"] : $categoryOrCategoryID;
            $this->api()->patch("/categories/$categoryID/preferences/$userID", $preferences);
        }, $userOrUserID);
    }

    /**
     * Assert that a category has a specific allowedDiscussionTypes.
     *
     * @param $expected array|string
     * @param $actual array|int
     */
    public function assertCategoryAllowedDiscussionTypes($expected, $actual): void
    {
        if (!is_array($expected)) {
            $expected = [$expected];
        }

        if (!is_array($actual)) {
            $actual = $this->categoryModel->getID($actual, DATASET_TYPE_ARRAY);
        }

        $permissionCategory = $this->categoryModel::permissionCategory($actual);
        $result = $this->categoryModel->getAllowedDiscussionData($permissionCategory, $actual);
        $this->assertEquals($expected, array_keys($result));
    }

    /**
     * Make a reaction on a discussion.
     *
     * @param $discussionOrDiscussionID
     * @param string $reactionType
     */
    public function reactDiscussion($discussionOrDiscussionID, string $reactionType)
    {
        $discussionID = is_array($discussionOrDiscussionID)
            ? $discussionOrDiscussionID["discussionID"]
            : $discussionOrDiscussionID;
        $response = $this->api()->post(
            "/discussions/$discussionID/reactions",
            [
                "reactionType" => $reactionType,
            ],
            [],
            ["throw" => false]
        );
        if (!$response->isSuccessful() && $response->getStatusCode() !== 410) {
            // 410 is a known response to a spam reaction.
            throw $response->asException();
        }
    }

    /**
     * Generates and returns a salt
     *
     * @return string
     */
    private function generateCollectionSalt(): string
    {
        return "-" . round(microtime(true) * 1000) . rand(1, 1000);
    }

    /**
     * Return a mocked rich body.
     *
     * @param string $content
     * @return string
     */
    public static function richBody(string $content): string
    {
        return "[{\"type\":\"p\",\"children\":[{\"text\":\"{$content}\"}]}]";
    }
}

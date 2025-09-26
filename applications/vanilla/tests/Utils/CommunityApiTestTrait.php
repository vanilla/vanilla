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
use Garden\Sites\Clients\SiteHttpClient;
use Gdn_Format;
use PHPUnit\Framework\TestCase;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Http\InternalClient;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Utility\ModelUtils;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\Http\TestHttpClient;
use VanillaTests\VanillaTestCase;

/**
 * @method TestHttpClient api()
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
    protected $lastInsertedInterestID = null;

    /** @var int|null */
    protected $lastInsertedCollectionID = null;

    protected $lastResponse = null;

    /** @var int|null */
    protected $lastReportID = null;

    /** @var int|null */
    protected $lastEscalationID = null;

    /** @var string|null */
    protected $lastPostTypeID = null;

    /** @var string|null */
    protected $lastPostFieldID = null;

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
        $this->lastInsertedInterestID = null;
        $this->lastInsertedCollectionID = null;
        $this->lastResponse = null;
        $this->lastReportID = null;
        $this->lastEscalationID = null;
        $this->lastPostTypeID = null;
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
     * Assert that an escalation exists for a record.
     *
     * @param array $record A record specifying "commentID", "discussionID", or "escalationID".
     * @param array $expectedEscalationFields Dot-notation fields to assert about the escalation.
     * @return array The escalation.
     */
    protected function assertEscalationForRecord(array $record, array $expectedEscalationFields = []): array
    {
        $query = $this->prepareQueryForRecord($record);
        $escalations = $this->api()
            ->get("/escalations", $query)
            ->getBody();
        if (empty($escalations)) {
            $this->fail("No escalations found for query:\n" . json_encode($query, JSON_PRETTY_PRINT));
        }
        $this->assertCount(1, $escalations, "Expected 1 escalation for record.");
        $escalation = $escalations[0];
        if (!empty($expectedEscalationFields)) {
            $this->assertDataLike($expectedEscalationFields, $escalation);
        }
        return $escalation;
    }

    /**
     * Assert that a report exists for a record.
     *
     * @param array $record A record specifying "commentID", "discussionID", or "escalationID".
     * @param array $expectedReportFields Dot-notation fields to assert about the report.
     * @return array The report.
     */
    protected function assertReportForRecord(array $record, array $expectedReportFields = []): array
    {
        $query = $this->prepareQueryForRecord($record);
        $reports = $this->api()
            ->get("/reports", $query)
            ->getBody();
        $this->assertCount(1, $reports, "Expected 1 report for record.");
        $report = $reports[0];
        if (!empty($expectedReportFields)) {
            $this->assertDataLike($expectedReportFields, $report);
        }
        return $report;
    }

    /**
     * @param array $record
     * @return array
     */
    private function prepareQueryForRecord(array $record): array
    {
        if (isset($record["commentID"])) {
            $query = [
                "recordType" => "comment",
                "recordID" => $record["commentID"],
            ];
        } elseif (isset($record["discussionID"])) {
            $query = [
                "recordType" => "discussion",
                "recordID" => $record["discussionID"],
            ];
        } elseif (isset($record["escalationID"])) {
            $query = [
                "escalationID" => $record["escalationID"],
            ];
        } elseif (isset($record["reportID"])) {
            $query = [
                "reportID" => $record["reportID"],
            ];
        } else {
            throw new \InvalidArgumentException("Record must have a commentID, discussionID or escalationID.");
        }
        return $query;
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
     * @param array $options
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function createDiscussion(array $overrides = [], array $extras = [], array $options = []): array
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

        $type = strtolower($overrides["type"] ?? "discussion");
        $apiUrl = "/discussions";
        if ($type !== "discussion") {
            $apiUrl .= "/" . strtolower($type);
        }

        $this->lastCommunityResponse = $this->api()->post($apiUrl, $params, options: $options);
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
     * Create Interest
     */
    public function createInterest(array $overrides = []): array
    {
        $interestName = $overrides["name"] ?? "interestName_" . VanillaTestCase::id("interestName");
        $interestApiName = $overrides["apiName"] ?? "interestApiName_" . VanillaTestCase::id("interestApiName");
        $params = $overrides + [
            "name" => $interestName,
            "apiName" => $interestApiName,
            "categoryIDs" => [$this->lastInsertedCategoryID],
            "profileFieldMapping" => [],
        ];

        $apiUrl = "/interests";

        $this->lastCommunityResponse = $this->api()->post($apiUrl, $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastInsertedInterestID = $result["interestID"] ?? null;
        if ($this->lastInsertedInterestID === null) {
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
     * Bookmark a discussion as a specific user.
     *
     * @param int|array $discussionOrDiscussionID The discussion to bookmark
     * @param array $user The user to bookmark as
     */
    public function bookmarkDiscussionWithUser(int|array $discussionOrDiscussionID, array $user): void
    {
        $this->runWithUser(function () use ($discussionOrDiscussionID) {
            $this->bookmarkDiscussion($discussionOrDiscussionID);
        }, $user);
    }

    /**
     * Set a discussion's score.
     *
     * @param int $discussionID The discussion to set the score for.
     * @param int $score The score to set.
     */
    public function setDiscussionScore(int $discussionID, int $score)
    {
        /** @var \DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
        $discussionModel->setField($discussionID, "Score", $score);
    }

    /**
     * Create an escalation comment.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     * @throws Exception
     */
    public function createEscalationComment(array $overrides = []): array
    {
        $overrides["parentRecordType"] = "escalation";
        if (!isset($overrides["parentRecordID"])) {
            // See if we have an escalationID
            if ($this->lastEscalationID === null) {
                throw new Exception("Could not insert a test escalation comment because no escalation was specified.");
            }

            $overrides["parentRecordID"] = $this->lastEscalationID;
        }

        $params = $overrides + [
            "format" => TextFormat::FORMAT_KEY,
            "body" => "Hello Comment",
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
     * Create a nested comment.
     *
     * @param array $parentComment The parent comment API data.
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     * @throws Exception
     */
    public function createNestedComment(array $parentComment, array $overrides = []): array
    {
        $parentCommentID = $parentComment["commentID"];
        return $this->createComment($overrides + ["parentCommentID" => $parentCommentID]);
    }

    /**
     * Create a comment.
     *
     * @param array $overrides Fields to override on the insert.
     * @param array $extras Fields to set directly in the DB.
     *
     * @return array
     * @throws Exception
     */
    public function createComment(array $overrides = [], array $extras = []): array
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

        if (!empty($extras)) {
            /** @var \CommentModel $commentModel */
            $commentModel = \Gdn::getContainer()->get(\CommentModel::class);
            $commentModel->setField($this->lastInsertCommentID, $extras);
            ModelUtils::validationResultToValidationException($commentModel);
            $this->lastCommunityResponse = $this->api()->get("/comments/{$result["commentID"]}");
            $result = $this->lastCommunityResponse->getBody();
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

        if (!empty($attachmentData)) {
            // Update media with $attachmentData
            $response = $this->api()->patch("/media/{$mediaID}/attachment", $attachmentData);
            $result = $response->getBody();
        }

        return $result;
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
     * Return the attachments based on a foreignID.
     *
     * @param string $foreignID
     * @return array
     */
    public function getAttachment(string $foreignID): array
    {
        $attachmentModel = $this->container()->get(AttachmentModel::class);
        $attachments = $attachmentModel->getWhere(["ForeignID" => $foreignID])->resultArray();
        return $attachments;
    }

    /**
     * Create a post type.
     *
     * @param array $overrides
     * @return array
     */
    public function createPostType(array $overrides = []): array
    {
        $params = $overrides + [
            "postTypeID" => "posttypeid-" . VanillaTestCase::id("posttypeid"),
            "name" => "posttypename-" . VanillaTestCase::id("posttypename"),
            "parentPostTypeID" => "discussion",
            "postButtonLabel" => "New Post " . VanillaTestCase::id("postbuttonlabel"),
            "isActive" => true,
            "isDeleted" => false,
        ];

        $this->lastCommunityResponse = $this->api()->post("/post-types", $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastPostTypeID = $result["postTypeID"];
        return $result;
    }

    /**
     * Create a post field.
     *
     * @param array $overrides
     * @return array
     * @throws Exception
     */
    public function createPostField(array $overrides = []): array
    {
        $params = $overrides + [
            "postFieldID" => "postfieldid-" . VanillaTestCase::id("postField"),
            "dataType" => "text",
            "label" => "field label",
            "description" => "field description",
            "formType" => "text",
            "visibility" => "public",
            "isRequired" => false,
            "isActive" => true,
        ];

        $this->lastCommunityResponse = $this->api()->post("/post-fields", $params);
        $result = $this->lastCommunityResponse->getBody();
        $this->lastPostFieldID = $result["postFieldID"];
        return $result;
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
     * React to a comment.
     *
     * @param $commentOrCommentID
     * @param string $reactionType
     *
     * @return void
     */
    public function reactComment($commentOrCommentID, string $reactionType): void
    {
        $commentID = is_array($commentOrCommentID) ? $commentOrCommentID["commentID"] : $commentOrCommentID;

        $response = $this->api()->post(
            "/comments/$commentID/reactions",
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

    /**
     * @param int|array $discussionOrDiscussionID
     * @param bool $isResolved
     *
     * @return void
     */
    private function assertDiscussionResolved(int|array $discussionOrDiscussionID, bool $isResolved = true): void
    {
        $discussionID = is_array($discussionOrDiscussionID)
            ? $discussionOrDiscussionID["discussionID"]
            : $discussionOrDiscussionID;

        $discussion = $this->api()
            ->get("/discussions/{$discussionID}")
            ->getBody();

        $messagePhrase = $isResolved ? "to be resolved" : "to be unresolved";
        $this->assertSame(
            $isResolved,
            $discussion["resolved"],
            "Expected discussion {$discussion["discussionID"]} {$messagePhrase}."
        );
        if ($isResolved) {
            $this->assertEquals(
                RecordStatusModel::DISCUSSION_STATUS_RESOLVED,
                $discussion["internalStatusID"],
                "Expected internalStatusID to be resolved."
            );
        } else {
            TestCase::assertNotEquals(
                RecordStatusModel::DISCUSSION_STATUS_RESOLVED,
                $discussion["internalStatusID"],
                "Expected internalStatusID to not be resolved."
            );
        }
    }

    /**
     * Assert that a certain user has particular permissions on a discussion.
     *
     * @param array $expectedPermissions
     * @param array $discussion
     * @param array|int $user
     * @param string $message
     * @return void
     */
    private function assertUserHasDiscussionPermissions(
        array $expectedPermissions,
        array $discussion,

        array|int $user,
        string $message = ""
    ): void {
        $fetchedDiscussion = $this->runWithUser(function () use ($discussion) {
            return $this->api()
                ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "permissions"])
                ->assertSuccess()
                ->getBody();
        }, $user);

        $permissions = $fetchedDiscussion["permissions"] ?? null;
        $this->assertNotEmpty($permissions, "Permissions not found in discussion response.");
        $this->assertPermissionSubset($expectedPermissions, $permissions, $message);
    }

    /**
     * Assert that a certain user has particular permissions on a category.
     *
     * @param array $expectedPermissions
     * @param array $category
     * @param array|int $user
     * @param string $message
     * @return void
     */
    private function assertUserHasCategoryDiscussionPermissions(
        array $expectedPermissions,
        array $category,
        array|int $user,
        string $message = ""
    ): void {
        $fetchedDiscussion = $this->runWithUser(function () use ($category) {
            return $this->api()
                ->get("/categories/{$category["categoryID"]}", ["expand" => "permissions"])
                ->assertSuccess()
                ->getBody();
        }, $user);

        $permissions = $fetchedDiscussion["permissions"] ?? null;
        $this->assertNotEmpty($permissions, "Permissions not found in category response.");
        $this->assertPermissionSubset($expectedPermissions, $permissions, $message);
    }

    /**
     * @param array $expectedPermissions
     * @param array $permissions
     * @param string $message
     * @return void
     */
    private function assertPermissionSubset(array $expectedPermissions, array $permissions, string $message): void
    {
        $filteredActualPermissions = [];
        foreach ($expectedPermissions as $permissionName => $value) {
            $filteredActualPermissions[$permissionName] = $permissions[$permissionName] ?? null;
        }

        $this->assertEquals(
            $expectedPermissions,
            $filteredActualPermissions,
            $message ?: "Incorrect permissions found for user."
        );
    }

    /**
     * @param int $draftID
     * @return array|false
     */
    private function getLegacyDraft(int $draftID): array|false
    {
        $contentDraftModel = self::container()->get(ContentDraftModel::class);
        try {
            $draft = $contentDraftModel->selectSingle(where: ["draftID" => $draftID]);
            $draft = $contentDraftModel->convertToLegacyDraft($draft);
            return $draft;
        } catch (NoResultsException $ex) {
            return false;
        }
    }

    /**
     * Add a tag to a discussion.
     *
     * @param int|null $discussionID
     * @param int|null $tagID
     * @return void
     */
    public function tagDiscussion(?int $discussionID = null, ?int $tagID = null): void
    {
        $discussionID = $discussionID ?? $this->lastInsertedDiscussionID;
        $tagID = $tagID ?? $this->lastInsertedTagID;
        $this->api()
            ->post("/discussions/{$discussionID}/tags", [
                "tagIDs" => [$tagID],
            ])
            ->assertSuccess();
    }
}

<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Models\ContentDraftModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\ScheduledDraftTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 *  Test similar to drafts with scheduled drafts flag enabled
 */
class ScheduledDraftTest extends DraftsTest
{
    use UsersAndRolesApiTestTrait, ExpectExceptionTrait, ScheduledDraftTestTrait, CommunityApiTestTrait;

    protected $patchFields = ["parentRecordID", "attributes", "recordType"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        CurrentTimeStamp::mockTime(time());
        parent::setUp();
        $this->init();
        $this->setConfig("Tagging.Discussions.Enabled", true);
    }
    /**
     * Test deleting Scheduled record
     *
     * @return void
     */
    public function testDelete()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        $draft = $this->createScheduleDraft($record);
        $this->assertEquals("scheduled", $draft["draftStatus"]);
        $this->assertArrayHasKey("recordID", $draft);
        $this->assertNotEmpty($draft["recordID"]);
        $this->api()
            ->delete("/drafts/{$draft["draftID"]}")
            ->assertSuccess();
        $this->runWithExpectedExceptionMessage("Draft not found.", function () use ($draft) {
            $this->api()
                ->get($this->baseUrl . "/{$draft["draftID"]}")
                ->assertStatus(404);
        });
        $discussion = $this->discussionModel->getID($draft["recordID"]);
        $this->assertEmpty($discussion);
    }

    /**
     * Test Schedule Index filter and sort
     */
    public function testFilterAndSortScheduleIndex(): void
    {
        $this->resetTable("contentDraft");
        $overrides = [
            [
                "dateScheduled" => "2025-03-05T05:00:00+00:00",
                "dateUpdated" => "2025-02-05T00:00:00+00:00",
                "draftMeta" => ["name" => "Draft 1"],
                "draftStatus" => 1,
            ],
            [
                "dateScheduled" => "2025-01-01T08:00:00+00:00",
                "dateUpdated" => "2025-03-08T08:00:00+00:00",
                "draftMeta" => ["name" => "Draft 2"],
                "draftStatus" => 1,
            ],
            [
                "dateScheduled" => "2025-01-05T00:00:00+00:00",
                "dateUpdated" => "2025-02-05T00:00:00+00:00",
                "draftMeta" => ["name" => "Draft 3"],
                "draftStatus" => 1,
            ],
            [
                "dateScheduled" => "2025-02-10T10:00:00+00:00",
                "dateUpdated" => "2025-02-05T00:00:00+00:00",
                "draftMeta" => ["name" => "Draft 4"],
                "draftStatus" => 1,
            ],
        ];

        foreach ($overrides as $override) {
            $record = $this->scheduleDraftRecord($override);
            $this->createScheduleDraftRecord($record);
        }
        // Create few normal drafts
        for ($i = 0; $i < 2; $i++) {
            $this->testPost();
        }

        // Test that by default the records are filtered by draftStatus of draft
        $response = $this->api()
            ->get("/drafts")
            ->assertSuccess()
            ->assertJsonArray();
        $result = $response->getBody();
        $this->assertCount(2, $result);

        // make sure we get extra fields
        $extraFields = ["dateScheduled", "draftStatus", "failedReason", "editUrl", "breadCrumbs", "permaLink"];
        foreach ($extraFields as $field) {
            $this->assertArrayHasKey($field, $result[0]);
        }
        $this->assertEquals("draft", $result[0]["draftStatus"]);
        $this->assertEmpty($result[0]["failedReason"]);
        $this->assertEmpty($result[0]["dateScheduled"]);
        // Test filtering by draftStatus
        $response = $this->api()
            ->get("/drafts", ["draftStatus" => "scheduled"])
            ->assertSuccess()
            ->assertJsonArray()
            ->assertCount(4);
        $result = $response->getBody();
        foreach ($extraFields as $field) {
            $this->assertArrayHasKey($field, $result[0]);
        }
        $this->assertEquals("scheduled", $result[0]["draftStatus"]);

        // Test expands and sorting by dateScheduled
        $response = $this->api()
            ->get("/drafts", ["draftStatus" => "scheduled", "expand" => true, "sort" => "-dateScheduled"])
            ->assertSuccess()
            ->assertJsonArray();
        $result = $response->getBody();
        $this->assertArrayHasKey("name", $result[0]);
        $this->assertArrayHasKey("excerpt", $result[0]);
        $this->assertEquals(["Draft 1", "Draft 4", "Draft 3", "Draft 2"], array_column($result, "name"));

        $response = $this->api()->get("/drafts", [
            "draftStatus" => "scheduled",
            "expand" => true,
            "sort" => "dateScheduled",
        ]);
        $result = $response->getBody();
        $this->assertEquals(["Draft 2", "Draft 3", "Draft 4", "Draft 1"], array_column($result, "name"));

        // Test filtering scheduled drafts by dateScheduled

        $this->api()
            ->get("/drafts", [
                "draftStatus" => "scheduled",
                "dateScheduled" => "[2025-01-01, 2025-02-01]",
                "expand" => true,
            ])
            ->assertJsonArrayValues(["name" => ["Draft 2", "Draft 3"]], strictOrder: true, count: 2);
    }

    /**
     * Test covert schedule Draft to normal draft
     */
    public function testConvertScheduleDraftToDraft(): void
    {
        $now = CurrentTimeStamp::getDateTime();
        $override = [
            "dateScheduled" => $now->modify("+3 day")->format("c"),
            "draftMeta" => ["name" => "My new draft"],
        ];
        $record = $this->scheduleDraftRecord($override);
        $draft = $this->createScheduleDraft($record);
        $this->assertEquals("scheduled", $draft["draftStatus"]);
        $this->assertNotEmpty($draft["dateScheduled"]);
        $this->assertNotEmpty($draft["recordID"]);
        $discussionID = $draft["recordID"];

        $draft = $this->convertScheduleDraft($draft["draftID"]);
        $this->assertArrayNotHasKey("recordID", $draft);

        $discussion = $this->discussionModel->getID($discussionID);
        $this->assertEmpty($discussion);
    }

    /**
     *  Test update Schedule for a scheduled draft
     */
    public function testUpdateScheduleDate()
    {
        $now = CurrentTimeStamp::getDateTime();
        $scheduledDate = $now->modify("+3 day");
        $newScheduledDate = $now->modify("+5 day");
        $override = [
            "dateScheduled" => $scheduledDate->format("c"),
            "dateUpdated" => $now->modify("-2 day")->format("c"),
            "draftMeta" => ["name" => "My ScheduledDraft"],
            "draftStatus" => 1,
        ];
        $record = $this->scheduleDraftRecord($override);
        $draft = $this->createScheduleDraftRecord($record);
        $this->assertEquals(1, $draft["draftStatus"]);
        $this->assertNotEmpty($draft["dateScheduled"]);
        $this->assertEquals($scheduledDate->format(DATE_ATOM), $draft["dateScheduled"]->format(DATE_ATOM));

        // Test update schedule date
        $response = $this->api()
            ->patch("/drafts/schedule/{$draft["draftID"]}", ["dateScheduled" => $newScheduledDate->format("c")])
            ->assertSuccess();
        $result = $response->getBody();

        $dateScheduled = new \DateTimeImmutable($result["dateScheduled"]);

        $this->assertEquals($newScheduledDate->format(DATE_ATOM), $dateScheduled->format(DATE_ATOM));
    }

    /**
     * Test Scheduled draft post throws permission error if user dont have "Schedule.Allow" permission
     */
    public function testSchedulePostThrowPermissionError()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER, "mem");
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () use ($record) {
            $this->api()
                ->post($this->baseUrl, $record)
                ->assertStatus(404);
        }, $userID);
    }

    /**
     * Test validation for scheduled drafts on post endpoint
     *
     * @return void
     */
    public function testPostScheduleDraftValidation()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        foreach (["format", "body", "draftMeta.name", "draftMeta.postTypeID"] as $field) {
            $postData = $record;
            $field = explode(".", $field);
            if (count($field) > 1) {
                $key = $field[1];
                $postData["attributes"][$field[0]][$field[1]] = "";
            } else {
                $key = $field[0];
                $postData["attributes"][$field[0]] = "";
            }
            $this->runWithExpectedExceptionMessage("$key is required.", function () use ($postData) {
                $this->api()
                    ->post("/drafts", $postData)
                    ->assertStatus(400);
            });
        }
    }

    /**
     * Test validation for scheduled dates for scheduled drafts on post endpoint.
     *
     * @return void
     * @dataProvider getScheduledDates
     */
    public function testPostScheduleDateValidation(string $dateString, string $errorMessage)
    {
        $currentTime = CurrentTimeStamp::getDateTime();
        $dateScheduled = $dateString !== "" ? $currentTime->modify($dateString)->format("c") : $dateString;
        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "dateScheduled" => $dateScheduled,
        ]);
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($record) {
            $this->api()
                ->post("/drafts", $record)
                ->assertStatus(400);
        });
    }

    /**
     * Data provider for testPostScheduleDateValidation
     *
     * @return array[]
     * @throws \DateMalformedStringException
     */
    public function getScheduledDates(): array
    {
        return [
            "emptyDate" => [
                "dateScheduled" => "",
                "message" => "dateScheduled is not a valid datetime.",
            ],
            "pastDate" => [
                "dateScheduled" => "-1 day",
                "message" => "The scheduled date and time must be at least 15 minutes in the future.",
            ],
            "currentDate + 15 minutes" => [
                "dateScheduled" => "+10 minutes",
                "message" => "The scheduled date and time must be at least 15 minutes in the future.",
            ],
            "futureDate > year" => [
                "dateScheduled" => "+1 year +2 days",
                "message" => "The scheduled date and time must be less than 1 year from now.",
            ],
        ];
    }

    /**
     * Test creating a test discussion with scheduled type
     *
     * @return void
     * @throws \Exception
     */
    public function testCreatePlaceHolderPostRecord()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        $result = $this->createScheduleDraft($record);

        $discussionID = $result["recordID"];
        $this->assertNotEmpty($discussionID);
        // We need to make sure the discussion is not visible to the public
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Discussion not found.");
        $this->api()
            ->get("/discussions/{$discussionID}")
            ->assertStatus(404);
    }

    /**
     * Test draft post for scheduled record
     *
     * @return mixed
     */
    public function testPostScheduleDraft()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        $draft = $this->createScheduleDraft($record);
        $this->assertEquals("scheduled", $draft["draftStatus"]);
        $this->assertArrayHasKey("recordID", $draft);
        $this->assertNotEmpty($draft["recordID"]);
        return $draft;
    }

    /**
     * Test scheduled draft patch test
     *
     * @depends testPostScheduleDraft
     */
    public function testPatchScheduleDraft($draft)
    {
        $draftID = $draft["draftID"];

        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "dateScheduled" => CurrentTimeStamp::getDateTime()
                ->modify("+1 day")
                ->format("c"),
            "draftMeta" => [
                "name" => "Update existing draft",
            ],
        ]);
        $updatedDraft = $this->updateScheduleDraft($draftID, $record);

        $this->assertNotEquals(
            $draft["attributes"]["draftMeta"]["name"],
            $updatedDraft["attributes"]["draftMeta"]["name"]
        );
        $this->assertEquals(
            $record["attributes"]["draftMeta"]["name"],
            $updatedDraft["attributes"]["draftMeta"]["name"]
        );

        $this->assertEquals($draft["draftID"], $updatedDraft["draftID"]);
        $this->assertEquals($draft["recordID"], $updatedDraft["recordID"]);
    }

    /**
     * Test patch a regular draft to a scheduled draft
     *
     * @return void
     * @throws \DateMalformedStringException
     */
    public function testConvertRegularDraftToScheduledDraft()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "draft"]);
        unset($record["dateScheduled"]);
        $draft = $this->testPost($record);
        $this->assertEquals("draft", $draft["draftStatus"]);
        $this->assertNull($draft["dateScheduled"]);
        $draft["draftStatus"] = "scheduled";
        $draft["dateScheduled"] = CurrentTimeStamp::getDateTime()
            ->modify("+1 day")
            ->format("c");

        $draft["attributes"]["draftMeta"]["name"] = "Update this regular draft to a scheduled draft";
        $draftID = $draft["draftID"];

        $updatedDraft = $this->updateScheduleDraft($draftID, $draft);

        $this->assertArrayHasKey("recordID", $updatedDraft);
        $this->assertNotEmpty($updatedDraft["recordID"]);
        $this->assertEquals("scheduled", $updatedDraft["draftStatus"]);
    }

    /**
     * @inheritdoc
     * @return void
     */
    public function testViewingDraftComment()
    {
        $this->markTestSkipped();
    }

    /**
     * @inheritdoc
     * @return void
     */
    public function testCategoryPickerDefaultsToCategory()
    {
        $this->markTestSkipped();
    }

    /**
     * @inheritdoc
     * @return void
     */
    public function testGetEditFields()
    {
        $this->patchFields = array_merge($this->patchFields, ["draftStatus", "dateScheduled"]);
        parent::testGetEditFields();
    }

    /**
     * Test creating Scheduled Drafts for existing Discussion.
     */
    public function testCreateScheduleDraftsForExistingDiscussion(): array
    {
        $this->createCategory(["name" => "Scheduled Drafts"]);
        $discussion = $this->createDiscussion();
        $record = $this->scheduleDraftRecord(["draftStatus" => "scheduled"]);
        $record["recordID"] = $discussion["discussionID"];

        $draft = $this->createScheduleDraft($record);

        $this->assertArrayHasKey("recordID", $draft);
        $this->assertEquals($draft["recordID"], $discussion["discussionID"]);
        $this->assertEquals("scheduled", $draft["draftStatus"]);

        return $draft;
    }

    /**
     * Test converting existing Scheduled Drafts to Drafts.
     * @param array $draft
     * @return void
     * @depends testCreateScheduleDraftsForExistingDiscussion
     */
    public function testConvertExistingScheduleDraftToDraft(array $draft)
    {
        $convertedDraft = $this->convertScheduleDraft($draft["draftID"]);
        $this->assertArrayHasKey("recordID", $draft);
        $this->assertEquals($draft["recordID"], $convertedDraft["recordID"]);
    }

    /**
     * Test patching Scheduled Drafts for existing Discussion.
     * @param array $draft
     * @return void
     * @depends testCreateScheduleDraftsForExistingDiscussion
     */
    public function testPatchScheduleDraftForExistingDiscussion(array $draft): void
    {
        // update draft name and body
        $record = $draft;
        $record["attributes"]["draftMeta"]["name"] = "Updated Draft Name";
        $record["attributes"]["body"] = '[{"type":"p","children":[{"text":"this is updated drafts for testing"}]}]';
        $updatedDraft = $this->updateScheduleDraft($draft["draftID"], $record);

        $this->assertArrayHasKey("recordID", $updatedDraft);
        $this->assertEquals($draft["recordID"], $updatedDraft["recordID"]);

        $this->assertEquals(
            $record["attributes"]["draftMeta"]["name"],
            $updatedDraft["attributes"]["draftMeta"]["name"]
        );
        $this->assertEquals($draft["draftID"], $updatedDraft["draftID"]);
        $this->assertEquals($record["attributes"]["body"], $updatedDraft["attributes"]["body"]);

        // update draft status and it should still keep the recordID

        $record["draftStatus"] = "draft";
        $updatedDraft = $this->updateScheduleDraft($draft["draftID"], $record);

        $this->assertEmpty($updatedDraft["dateScheduled"]);
        $this->assertEquals("draft", $updatedDraft["draftStatus"]);
        $this->assertArrayHasKey("recordID", $updatedDraft);
        $this->assertEquals($draft["recordID"], $updatedDraft["recordID"]);
    }

    /**
     * Test post and patch for scheduled drafts
     *
     * @return void
     */
    public function testScheduleDraftWithPostType(): void
    {
        $this->createPostType(["name" => "Scheduled Post Type", "postTypeID" => "scheduled-post-type"]);
        $postTypeID = $this->lastPostTypeID;

        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "draftMeta" => [
                "name" => "Scheduled Draft With Post Type",
                "postTypeID" => $postTypeID,
            ],
        ]);

        $drafts = $this->createScheduleDraft($record);
        $this->assertEquals($postTypeID, $drafts["attributes"]["draftMeta"]["postTypeID"]);
        $recordID = $drafts["recordID"];

        $discussion = $this->discussionModel->getID($recordID);
        $this->assertEmpty($discussion);

        $drafts["attributes"]["body"] = '[{"type":"p","children":[{"text":"Updated scheduled post draft"}]}]';

        $updatedDraft = $this->updateScheduleDraft($drafts["draftID"], $drafts);

        $this->assertEquals($postTypeID, $updatedDraft["attributes"]["draftMeta"]["postTypeID"]);
        $this->assertEquals($recordID, $updatedDraft["recordID"]);
        $this->assertEquals($drafts["attributes"]["body"], $updatedDraft["attributes"]["body"]);
    }

    /**
     * Test that a draft with recordID can be scheduled only once
     *
     * @return void
     */
    public function testDraftWithRecordIDCanBeScheduledOnlyOnce()
    {
        $draft = $this->testCreateScheduleDraftsForExistingDiscussion();
        $keys = ["recordID", "recordType", "attributes", "draftStatus", "dateScheduled"];
        $record = array_intersect_key($draft, array_flip($keys));
        $this->runWithExpectedExceptionMessage(
            "An update is already scheduled for this item. Please wait for the scheduled update to be published, or cancel it before scheduling a new one.",
            function () use ($record) {
                $this->createScheduleDraft($record);
            }
        );

        // We should still be able to create a normal draft with the same recordID
        $record["draftStatus"] = "draft";
        unset($record["dateScheduled"]);
        $newDraft = $this->createScheduleDraft($record);
        $newDraft["draftStatus"] = "scheduled";
        $newDraft["dateScheduled"] = CurrentTimeStamp::getDateTime()
            ->modify("+1 day")
            ->format("c");

        // We should not be able to convert this draft to scheduled draft
        $this->runWithExpectedExceptionMessage(
            "An update is already scheduled for this item. Please wait for the scheduled update to be published, or cancel it before scheduling a new one.",
            function () use ($newDraft) {
                $this->updateScheduleDraft($newDraft["draftID"], $newDraft);
            }
        );

        //Delete current scheduled draft for the record

        $this->api()
            ->delete("/drafts/{$draft["draftID"]}")
            ->assertSuccess();

        // We should be able to convert the new draft to a scheduled draft
        $updatedScheduledDraft = $this->updateScheduleDraft($newDraft["draftID"], $newDraft);

        $this->assertEquals("scheduled", $updatedScheduledDraft["draftStatus"]);
        $this->assertEquals($newDraft["recordID"], $updatedScheduledDraft["recordID"]);
    }

    /**
     * Test that we get proper post data and url from draft
     */
    public function testGetDiscussionPostDataAndUrlFromDrafts()
    {
        $record = $this->scheduleDraftRecord(["draftStatus" => "draft"]);
        $result = $this->createScheduleDraft($record);
        [$postData, $url] = $this->draftModel->getDiscussionPostDataAndUrlFromDrafts($result);
        $this->assertEquals("/discussions", $url);
        $expected = [
            "body" => $result["attributes"]["body"],
            "format" => $result["attributes"]["format"],
            "name" => $result["attributes"]["draftMeta"]["name"],
            "categoryID" => $result["attributes"]["draftMeta"]["categoryID"],
            "postTypeID" => $result["attributes"]["draftMeta"]["postTypeID"],
            "type" => "Discussion",
            "draftID" => $result["draftID"],
        ];
        $this->assertEquals($expected, $postData);

        // Test for scheduled draft

        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
        ]);
        $result = $this->createScheduleDraft($record);
        [$postData, $url] = $this->draftModel->getDiscussionPostDataAndUrlFromDrafts($result);
        $this->assertEquals("/discussions", $url);
        $this->assertArrayHasKey("discussionID", $postData);
        $this->assertEquals($result["recordID"], $postData["discussionID"]);
    }

    /**
     * Test that existing tags are included when posting a scheduled draft.
     *
     * @return void
     */
    public function testScheduledDraftWithExistingTag(): void
    {
        $existingTag = $this->createTag(["name" => "existingTag"]);
        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "draftMeta" => [
                "tagsIDs" => [$existingTag["tagID"]],
            ],
        ]);

        $draftResult = $this->createScheduleDraft($record);
        $this->assertEquals([$existingTag["tagID"]], $draftResult["attributes"]["draftMeta"]["tagsIDs"]);
    }

    /**
     * Test that a new tags is created when scheduling a draft.
     *
     * @return void
     */
    public function testScheduledDraftWithNewTagWithPermission(): void
    {
        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "draftMeta" => [
                "newTagNames" => ["newTag"],
            ],
        ]);

        $draftResult = $this->createScheduleDraft($record);
        $this->assertEquals(["newTag"], $draftResult["attributes"]["draftMeta"]["newTagNames"]);
        $this->api()
            ->get("/tags")
            ->assertSuccess()
            ->assertJsonArrayContains(["name" => "newTag"]);
    }

    /**
     * Test that tags can be removed from a scheduled draft.
     *
     * @return void
     */
    public function testRemovingTagFromScheduledDraft(): void
    {
        $existingTag = $this->createTag(["name" => __FUNCTION__]);
        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "draftMeta" => [
                "tagsIDs" => [$existingTag["tagID"]],
            ],
        ]);

        $draftResult = $this->createScheduleDraft($record);
        $this->assertEquals([$existingTag["tagID"]], $draftResult["attributes"]["draftMeta"]["tagsIDs"]);

        $draftResult["attributes"]["draftMeta"]["tagsIDs"] = [];
        $updatedDraft = $this->updateScheduleDraft($draftResult["draftID"], $draftResult);
        $this->assertEmpty($updatedDraft["attributes"]["draftMeta"]["tagsIDs"]);
    }

    /**
     * Test changing the announcement status of a scheduled draft.
     *
     * @return void
     */
    public function testAnnounce(): void
    {
        $record = $this->scheduleDraftRecord([
            "draftStatus" => "scheduled",
            "draftMeta" => [
                "pinLocation" => "recent",
            ],
        ]);

        // Create a scheduled draft with announcement status of "recent".
        $draftResult = $this->createScheduleDraft($record);
        $this->assertEquals("recent", $draftResult["attributes"]["draftMeta"]["pinLocation"]);

        // Remove announce status.
        $draftResult["attributes"]["draftMeta"]["pinLocation"] = "none";
        $draftResult["attributes"]["draftMeta"]["pinned"] = false;
        $updatedDraft = $this->updateScheduleDraft($draftResult["draftID"], $draftResult);
        $this->assertEquals("none", $updatedDraft["attributes"]["draftMeta"]["pinLocation"]);
    }

    /**
     * Test that an error is thrown when trying to create a scheduled draft with a new tag if the user does not have
     * the "tags.add" permission.
     *
     * @return void
     */
    public function testScheduledDraftWithNewTagWithoutPermission(): void
    {
        $member = $this->createUser();

        $this->runWithUser(function () {
            $this->runWithExpectedExceptionMessage("Permission Problem", function () {
                $record = $this->scheduleDraftRecord([
                    "draftStatus" => "scheduled",
                    "draftMeta" => [
                        "newTagNames" => ["newTag"],
                    ],
                ]);

                $this->createScheduleDraft($record);
            });
        }, $member["userID"]);
    }
}

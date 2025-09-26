<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\CurrentTimeStamp;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\ScheduledDraftTestTrait;
use VanillaTests\SiteTestCase;

class ScheduledQuestionDraftTest extends SiteTestCase
{
    use ScheduledDraftTestTrait, ExpectExceptionTrait;

    protected static $addons = ["QnA"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->init();
    }
    /**
     * Test adding a scheduled question draft.
     *
     * @return array
     */
    public function testScheduledQuestionDraft(): array
    {
        $this->createCategory(["name" => "Question Category"]);
        $record = $this->scheduleDraftRecord([
            "draftMeta" => [
                "categoryID" => $this->lastInsertedCategoryID,
                "postTypeID" => "question",
                "name" => "New Question Draft",
            ],
            "draftStatus" => "scheduled",
        ]);

        $draft = $this->createScheduleDraft($record);

        $this->assertEquals("scheduled", $draft["draftStatus"]);
        $this->assertArrayHasKey("recordID", $draft);
        $this->assertNotEmpty($draft["recordID"]);

        $discussionID = $draft["recordID"];
        $this->runWithExpectedExceptionMessage("Discussion not found.", function () use ($discussionID) {
            $this->api()
                ->get("/discussions/{$discussionID}")
                ->assertStatus(404);
        });
        return $draft;
    }

    /**
     * Test patch a scheduled question draft.
     *
     * @param array $draft
     * @return void
     * @depends testScheduledQuestionDraft
     */

    public function testPatchScheduledQuestionDraft(array $draft): void
    {
        $draftID = $draft["draftID"];
        $draft["attributes"]["draftMeta"]["name"] = "Updated question draft";
        $draft["dateScheduled"] = CurrentTimeStamp::getDateTime()
            ->modify("+1 day")
            ->format("c");
        $updatedDraft = $this->api()
            ->patch("/drafts/{$draftID}", $draft)
            ->assertSuccess()
            ->getBody();

        $this->assertEquals(
            $draft["attributes"]["draftMeta"]["name"],
            $updatedDraft["attributes"]["draftMeta"]["name"]
        );
        $this->assertEquals($draft["recordID"], $updatedDraft["recordID"]);
    }

    /**
     * Test that we get proper post data and url from draft
     *
     * @return void
     */
    public function testGetDiscussionPostDataAndUrlFromDrafts(): void
    {
        $draft = $this->testScheduledQuestionDraft();
        [$postData, $url] = $this->draftModel->getDiscussionPostDataAndUrlFromDrafts($draft);

        $this->assertEquals("/discussions/question", $url);
        $this->assertArrayHasKey("discussionID", $postData);
        $this->assertEquals($draft["recordID"], $postData["discussionID"]);
        $this->assertEquals("Question", $postData["type"]);
    }
}

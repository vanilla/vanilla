<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use Vanilla\Models\ContentDraftModel;

/**
 * Like {@link DraftsTest} but runs with the new community drafts feature flag enabled.
 */
class NewDraftsTest extends DraftsTest
{
    protected bool $useFeatureFlag = true;

    protected $patchFields = ["parentRecordID", "attributes", "recordType"];

    /**
     * @return void
     */
    public function testStoreArbitraryDraftData(): void
    {
        $expectedShape = [
            "recordType" => "my-type",
            "attributes.anything" => "I want",
            "attributes.expecting" => "to get it back",
        ];
        $draft = $this->api()
            ->post("/drafts", [
                "recordType" => "my-type",
                "attributes" => [
                    "anything" => "I want",
                    "expecting" => "to get it back",
                ],
            ])
            ->assertSuccess()
            ->assertJsonObject()
            ->assertJsonObjectLike($expectedShape);

        $this->api()
            ->get("/drafts/{$draft["draftID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike($expectedShape);
    }

    /**
     *  Test creating a draft with invalid recordID throws error
     */
    public function testCreateDraftWithInvalidRecordID(): void
    {
        $record = [
            "recordID" => 999,
            "recordType" => "discussion",
            "attributes" => [
                "body" => '[{"type":"p","children":[{"text":"this is a test body for draft testing"}]}]',
                "format" => "rich2",
                "draftType" => "discussion",
                "draftMeta" => [
                    "name" => "Draft for existing discussion",
                    "postMeta" => [],
                    "tags" => [],
                    "pinLocation" => "none",
                    "categoryID" => 1,
                ],
            ],
        ];
        $this->expectExceptionMessage("Discussion not found.");
        $this->api()
            ->post("/drafts", $record)
            ->assertStatus(400);
    }

    /**
     * Test creating Drafts for existing Discussion.
     *
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function testCreateDraftsForExistingDiscussion(): array
    {
        $discussion = $this->createDiscussion();
        $record = [
            "recordID" => $discussion["discussionID"],
            "recordType" => "discussion",
            "attributes" => [
                "body" => '[{"type":"p","children":[{"text":"this is a test body for draft testing"}]}]',
                "format" => "rich2",
                "draftType" => "discussion",
                "draftMeta" => [
                    "name" => "Draft for existing discussion",
                    "postMeta" => [],
                    "tags" => [],
                    "pinLocation" => "none",
                    "categoryID" => $discussion["categoryID"],
                ],
            ],
        ];
        $draft = $this->testPost($record);
        $this->assertArrayHasKey("recordID", $draft);
        $this->assertEquals($draft["recordID"], $discussion["discussionID"]);
        return $draft;
    }

    /**
     * Test patching Drafts for existing Discussion.
     *
     * @return void
     * @depends testCreateDraftsForExistingDiscussion
     */
    public function testPatchDraftForExistingDiscussion(array $draft): void
    {
        $record = $draft;
        $record["attributes"]["draftMeta"]["name"] = "Update the current draft for existing discussion";
        $response = $this->api()
            ->patch("{$this->baseUrl}/{$draft[$this->pk]}", $record)
            ->assertSuccess();
        $updatedDraft = $response->getBody();
        $this->assertEquals(
            $record["attributes"]["draftMeta"]["name"],
            $updatedDraft["attributes"]["draftMeta"]["name"]
        );
        $this->assertEquals($draft["recordID"], $updatedDraft["recordID"]);
        $this->assertEquals($draft["draftID"], $updatedDraft["draftID"]);
    }

    /**
     * Test Draft count queries
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testDraftCount()
    {
        $this->resetTable("contentDraft");
        $this->testPostDiscussion();
        $data = [
            "recordType" => "comment",
            "parentRecordID" => 1,
            "attributes" => [
                "announce" => 1,
                "body" => "Hello world.",
                "closed" => 1,
                "format" => "Markdown",
                "name" => "Discussion Draft",
                "sink" => 0,
                "tags" => "interesting,helpful",
                "type" => "discussion",
                "groupID" => null,
            ],
        ];
        parent::testPost($data);

        $article = [
            "recordType" => "article",
            "parentRecordID" => 1,
            "attributes" => [
                "announce" => 1,
                "body" => "Hello world.",
                "closed" => 1,
                "format" => "Markdown",
                "name" => "Article Draft",
                "sink" => 0,
                "tags" => "interesting,helpful",
                "type" => "article",
                "groupID" => null,
            ],
        ];
        parent::testPost($article);

        $contentDraftModel = $this->container()->get(ContentDraftModel::class);
        $this->setConfig("Feature.DraftScheduling.Enabled", true);

        //total draft count
        $count = $contentDraftModel->draftsWhereCountByUser();
        $this->assertEquals(3, $count);

        $this->setConfig("Feature.DraftScheduling.Enabled", false);
        // Discussion/comment count
        $count = $contentDraftModel->draftsWhereCountByUser();
        $this->assertEquals(2, $count);
        // Article count
        $count = $contentDraftModel->draftsCount(\Gdn::session()->UserID);
        $this->assertEquals(1, $count);
    }
}

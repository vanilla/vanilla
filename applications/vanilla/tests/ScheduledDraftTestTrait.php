<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC.
 * @license Proprietary
 */

namespace VanillaTests\Forum;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Models\ContentDraftModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * trait for the schedule draft tests
 */
trait ScheduledDraftTestTrait
{
    use CommunityApiTestTrait;

    protected $baseUrl = "/drafts";

    protected string $discussionUrl = "discussions";

    protected \DiscussionModel $discussionModel;

    protected ContentDraftModel $draftModel;

    public function init(): void
    {
        $this->enableFeature(ContentDraftModel::FEATURE);
        $this->enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        $this->draftModel = $this->container()->get(ContentDraftModel::class);
        $this->draftModel::structure(\Gdn::database()->structure());
    }

    /**
     * create a schedule draft record
     * @param array $overrides
     */
    protected function scheduleDraftRecord(array $overrides = []): array
    {
        $categoryID =
            $overrides["parentRecordID"] ?? ($overrides["draftMeta"]["categoryID"] ?? $this->lastInsertedCategoryID);
        if (empty($categoryID)) {
            $this->createCategory();
            $categoryID = $this->lastInsertedCategoryID;
        }

        return [
            "recordType" => $overrides["recordType"] ?? "discussion",
            "attributes" => ($overrides["attributes"] ?? []) + [
                "body" => $overrides["body"] ?? "[{\"type\":\"p\",\"children\":[{\"text\":\"this is a test body\"}]}]",
                "format" => $overrides["format"] ?? "rich2",
                "draftType" => "discussion",
                "parentRecordType" => "category",
                "parentRecordID" => $categoryID,
                "draftMeta" => array_merge(
                    [
                        "name" => "Test Draft",
                        "pinLocation" => null,
                        "categoryID" => $categoryID,
                        "postTypeID" => "discussion",
                    ],
                    $overrides["draftMeta"] ?? []
                ),
            ],
            "dateScheduled" =>
                $overrides["dateScheduled"] ??
                CurrentTimeStamp::getDateTime()
                    ->modify("+1 day")
                    ->format("c"),
            "draftStatus" => $overrides["draftStatus"] ?? "scheduled",
        ];
    }

    /**
     * create a schedule Draft
     *
     * @param array $record
     * @return array
     */
    protected function createScheduleDraft(array $record): array
    {
        $response = $this->api()
            ->post($this->baseUrl, $record)
            ->assertSuccess();
        return $response->getBody();
    }

    /**
     * create a new Schedule Draft record
     * @param array $record
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    protected function createScheduleDraftRecord(array $record): array
    {
        $recordID = $this->draftModel->insert($record);
        return $this->draftModel->selectSingle(["draftID" => $recordID]);
    }

    /**
     * Convert an existing scheduled draft to normal draft
     *
     * @param int $draftID
     * @return array
     */
    protected function convertScheduleDraft(int $draftID): array
    {
        $response = $this->api()
            ->patch("/drafts/cancel-schedule/$draftID")
            ->assertSuccess();
        $result = $response->getBody();

        $this->assertEquals("draft", $result["draftStatus"]);
        $this->assertEmpty($result["dateScheduled"]);
        return $result;
    }

    /**
     * update a schedule draft
     *
     * @param int $draftID
     * @param array $record
     * @return array
     */
    protected function updateScheduleDraft(int $draftID, array $record): array
    {
        $response = $this->api()
            ->patch("/drafts/$draftID", $record)
            ->assertSuccess();
        return $response->getBody();
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Models\CollectionRecordProviderInterface;

/**
 * Provide discussion collection records.
 */
class DiscussionCollectionProvider implements CollectionRecordProviderInterface
{
    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var \DiscussionsApiController */
    private $discussionsApiController;

    /**
     * DI.
     *
     * @param \DiscussionModel $discussionModel
     * @param \DiscussionsApiController $discussionsApiController
     */
    public function __construct(\DiscussionModel $discussionModel, \DiscussionsApiController $discussionsApiController)
    {
        $this->discussionModel = $discussionModel;
        $this->discussionsApiController = $discussionsApiController;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string
    {
        return \DiscussionModel::RECORD_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function filterValidRecordIDs(array $recordIDs): array
    {
        return $this->discussionModel->filterExistingRecordIDs($recordIDs);
    }

    /**
     * @inheritDoc
     */
    public function getRecords(array $recordIDs, string $locale): array
    {
        $query = [
            "discussionID" => $recordIDs,
            "expand" => [
                "category",
                "insertUser",
                "lastUser",
                "lastPost",
                "lastPost.body",
                "lastPost.insertUser",
                "raw",
                "tagIDs",
                "tags",
                "breadcrumbs",
                "-body",
                "excerpt",
                "status",
                "status.log",
            ],
        ];
        $stripKeys = ["bookmarked", "unread", "countUnread"];
        $result = $this->discussionsApiController->index($query);
        $discussionData = [];
        foreach ($result->getData() as $discussion) {
            foreach ($stripKeys as $stripKey) {
                if (array_key_exists($stripKey, $discussion)) {
                    unset($discussion[$stripKey]);
                }
            }
            $discussionData[$discussion["discussionID"]] = $discussion;
        }

        return $discussionData;
    }
}

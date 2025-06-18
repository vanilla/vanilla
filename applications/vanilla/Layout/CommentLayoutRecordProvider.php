<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CommentModel;
use DiscussionModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Models\CommentThreadModel;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;

/**
 * Handle resolving of discussion layout for a specific comment.
 */
class CommentLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "comment";

    /**
     * D.I.
     */
    public function __construct(
        private DiscussionModel $discussionModel,
        private CommentModel $commentModel,
        private ConfigurationInterface $configuration,
        private CommentThreadModel $commentThreadModel
    ) {
    }

    /**
     * Layouts can't be assigned to a discussion.
     *
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        return [];
    }

    /**
     * Layouts can't be assigned to a discussion.
     *
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool
    {
        return false;
    }

    /**
     * Returns an array of valid Record Types for this specific Record Provider.
     */
    public static function getValidRecordTypes(): array
    {
        return [self::RECORD_TYPE];
    }

    /**
     * @inheritdoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query;
    }

    /**
     * @return LayoutHydrator
     */
    private function layoutHydrator(): LayoutHydrator
    {
        return \Gdn::getContainer()->get(LayoutHydrator::class);
    }

    /**
     * @inheritdoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        $comment = $this->commentModel->getID($query->getRecordID(), DATASET_TYPE_ARRAY);
        if (!$comment) {
            throw new NotFoundException("Comment");
        }
        $this->commentModel->orderBy("c.DateInserted asc");
        // Skip loading Comment Pages number to avoid infinite loop.
        $skipPageCalculation = $query->getParams()["skipPageCalculation"] ?? false;
        $newQuery = $query
            ->withRecordType($comment["parentRecordType"])
            ->withRecordID($comment["parentRecordID"])
            ->withAdditionalParams([
                "page" => $skipPageCalculation ? 1 : $this->commentModel->getCommentThreadPage($comment),
                "parentRecordType" => $comment["parentRecordType"],
                "parentRecordID" => $comment["parentRecordID"],
                "commentID" => $query->recordID,
            ]);

        if ($query->layoutViewType === "comment") {
            // Our querying didn't know if this comment was an event comment or a post comment, so we'll work it out.
            $newQuery = $newQuery->withLayoutViewType($comment["parentRecordType"]);
        }
        return $newQuery;
    }
}

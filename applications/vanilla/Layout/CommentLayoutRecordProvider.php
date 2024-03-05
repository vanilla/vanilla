<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CategoryModel;
use CommentModel;
use DiscussionModel;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Site\SiteSectionModel;

/**
 * Handle resolving of discussion layout for a specific comment.
 */
class CommentLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "comment";

    private DiscussionModel $discussionModel;

    private CommentModel $commentModel;

    private ConfigurationInterface $configuration;

    /**
     * D.I.
     *
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        DiscussionModel $discussionModel,
        CommentModel $commentModel,
        ConfigurationInterface $configuration
    ) {
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->configuration = $configuration;
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
     * @inheritDoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        $comment = $this->commentModel->getID($query->getRecordID(), DATASET_TYPE_ARRAY);
        $discussion = $this->discussionModel->getID($comment["DiscussionID"], DATASET_TYPE_ARRAY);
        $discussionID = $discussion["DiscussionID"];

        $this->commentModel->orderBy("c.DateInserted asc");
        $offset = $this->commentModel->getOffset($comment["CommentID"]);
        $limit = $this->configuration->get("Vanilla.Comments.PerPage", 30);

        $page = (int) pageNumber($offset, $limit, false);

        $newQuery = $query->withRecordType("discussion")->withRecordID($discussionID);
        $newQuery->setParams([
            "page" => $page,
            "discussionID" => $discussionID,
        ]);

        return $newQuery;
    }
}

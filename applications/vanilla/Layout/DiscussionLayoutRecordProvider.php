<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CategoryModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Site\SiteSectionModel;

/**
 * Handle resolving of discussion layouts.
 *
 * Notably no layout types can be assigned to a discussion, but we can resolve a layout from a discussion (by using it's category).
 */
class DiscussionLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "discussion";

    private CategoryModel $categoryModel;
    private \DiscussionModel $discussionModel;

    /**
     * @param CategoryModel $categoryModel
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(CategoryModel $categoryModel, \DiscussionModel $discussionModel)
    {
        $this->categoryModel = $categoryModel;
        $this->discussionModel = $discussionModel;
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
     * Immediately resolve to the discussion's category. No need to wait until the parent query comes.
     * {@link CategoryLayoutRecordProvider} will take it from here.
     *
     * @inheritDoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        $discussion = $this->discussionModel
            ->createSql()
            ->from("Discussion")
            ->select(["CategoryID", "Type"])
            ->where("DiscussionID", $query->getRecordID())
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        if (!$discussion) {
            throw new NotFoundException("Discussion", [
                "discussionID" => $query->recordID,
            ]);
        }

        // TODO: When we implement alternative layout types for the discussion types handle that resolution here.
        $discussionType = strtolower($discussion["Type"] ?? "discussion");

        $categoryID = $discussion["CategoryID"];

        return $query->withRecordType(CategoryLayoutRecordProvider::RECORD_TYPE)->withRecordID($categoryID);
    }

    /**
     * @inheritDoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        // Relying on this occuring in the parent query.
        return $this->resolveLayoutQuery($query);
    }
}

<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use CategoryModel;
use DiscussionModel;
use Exception;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Gdn_Database;
use Gdn_SQLDriver;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Database\AlwaysBooleanExpression;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Exception\PermissionException;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\PermissionCheckTrait;

/**
 * Manage the interaction between discussions and comments.
 */
class DiscussionCommentModel extends AbstractCommentParentHandler
{
    const RECORD_TYPE = "discussion";

    /**
     * D.I.
     *
     * @param DiscussionModel $discussionModel
     * @param Gdn_Database $database
     */
    public function __construct(private DiscussionModel $discussionModel, private Gdn_Database $database)
    {
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return self::RECORD_TYPE;
    }

    /**
     * Verify if the current user has permission to view the comments of this discussion.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool
     * @throws NotFoundException
     */
    public function hasViewPermission(int $parentID, bool $throw = true): bool
    {
        return $this->discussionModel->hasViewPermission($parentID, $throw);
    }

    /**
     * Test that the current user has the proper permissions to add comments to this discussion.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool
     * @throws ClientException
     * @throws HttpException
     * @throws PermissionException
     */
    public function hasAddPermission(int $parentID, bool $throw = true): bool
    {
        return $this->runPermissionCallback($throw, function () use ($parentID) {
            $discussion = $this->ensureDiscussion($parentID);
            $categoryID = $discussion["CategoryID"];

            $isCategoryMod = $this->discussionModel->categoryPermission("posts.moderate", $categoryID, throw: false);

            if ($isCategoryMod) {
                return true;
            }

            // Make sure we can view the discussion.
            $this->discussionModel->categoryPermission("discussions.view", $categoryID);

            if ($discussion["Closed"]) {
                $hasDiscussionsClosePermission = $this->discussionModel->categoryPermission(
                    "discussions.close",
                    $categoryID,
                    throw: false
                );

                if (!$hasDiscussionsClosePermission) {
                    throw new ClientException(t("This discussion has been closed."));
                }
            }

            // Make sure we can add to the discussion.
            $this->discussionModel->categoryPermission("comments.add", $categoryID);

            return true;
        });
    }

    /**
     * @inheritdoc
     */
    protected function hasCommentParentEditPermissionBypass(array $commentRow): bool
    {
        $categoryID = $commentRow["CategoryID"];
        return $this->discussionModel->categoryPermission("comments.edit", $categoryID, throw: false);
    }

    /**
     * @inheritdoc
     */
    protected function hasCommentParentDeletePermissionBypass(array $commentRow): bool
    {
        $categoryID = $commentRow["CategoryID"];
        return $this->discussionModel->categoryPermission("comments.delete", $categoryID, throw: false);
    }

    /**
     * Get the category ID of a discussion.
     *
     * @param int $discussionID
     * @return array
     *
     * @throws ClientException
     */
    private function ensureDiscussion(int $discussionID): array
    {
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new NotFoundException("Discussion", ["discussionID" => $discussionID]);
        }
        return $discussion;
    }

    /**
     * Get the URL for a discussion comment.
     *
     * @param array $comment
     *
     * @return string
     */
    public function getCommentUrlPath(array $comment): string
    {
        $commentID = val("CommentID", $comment);

        return "/discussion/comment/{$commentID}#Comment_{$commentID}";
    }

    /**
     * Do nothing. This is handled directly in the CommentModel.
     *
     * @param array $comment
     * @return void
     */
    public function handleCommentInsert(array $comment): void
    {
        // Do nothing. This is handled directly in the CommentModel.
    }

    /**
     * Get a slot type based on the time since a discussion started.
     *
     * @param int $parentID discussionID
     * @return string
     * @throws NotFoundException
     */
    public function getAutoSlotType(int $parentID): string
    {
        $sql = $this->database->createSql();
        $dateInserted =
            $sql
                ->select("DateInserted")
                ->from("Discussion")
                ->where("DiscussionID", $parentID)
                ->get()
                ->firstRow()->DateInserted ?? null;
        if ($dateInserted === null) {
            throw new NotFoundException("Discussion", ["discussionID" => $parentID]);
        }
        return ModelUtils::getDateBasedSlotType($dateInserted);
    }

    /**
     * Make the necessary adjustments to the discussion when deleting one of its comments.
     *
     * @param array $comment
     * @return void
     * @throws Exception
     */
    public function handleCommentDelete(array $comment): void
    {
        // Do nothing. This is handled directly in the CommentModel.
    }

    /**
     * Fetch the parent record of a comment.
     *
     * @param int $parentID
     * @param bool $throw
     * @return array|false
     * @throws Exception
     */
    public function getParentRecord(int $parentID, bool $throw = true): array|false
    {
        return $this->discussionModel->getID($parentID, DATASET_TYPE_ARRAY);
    }

    /**
     * Fetch the parent record name of a comment.
     *
     * @param int $parentID
     * @return string
     * @throws Exception
     */
    public function getParentName(int $parentID): string
    {
        $discussion = $this->discussionModel->getID($parentID, DATASET_TYPE_ARRAY);
        return $discussion["Name"];
    }

    /**
     * Return the discussion category ID.
     *
     * @param int $parentID
     * @return int|null
     * @throws Exception
     */
    public function getCategoryID(int $parentID): ?int
    {
        $discussion = $this->discussionModel->getID($parentID, DATASET_TYPE_ARRAY);
        return $discussion["CategoryID"] ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function applyCommentQueryFiltering(Gdn_SQLDriver &$subQuery, array &$permissionWheres, array $where): void
    {
        $subQuery
            ->select(["d.Type as DiscussionType"])
            ->leftJoin("Discussion d", "c.parentRecordType=\"discussion\" AND c.parentRecordID=d.DiscussionID");

        $discussionID = $where["c.DiscussionID"] ?? ($where["DiscussionID"] ?? null);
        $parentRecordType = $where["c.parentRecordType"] ?? ($where["parentRecordType"] ?? null);
        $parentRecordID = $where["c.parentRecordID"] ?? ($where["parentRecordID"] ?? null);
        $discussionID = $parentRecordType === "discussion" ? $parentRecordID : $discussionID;

        $hasSingleRecordOptimization = $discussionID !== null && $this->hasViewPermission($discussionID, false);
        if ($hasSingleRecordOptimization) {
            $permissionWheres[] = new AlwaysBooleanExpression(true);
        } else {
            $permissionWheres[] = new CallbackWhereExpression(function (Gdn_SQLDriver $sql) {
                $this->discussionModel->applyDiscussionCategoryPermissionsWhere($sql);
            });
        }
    }

    /**
     * Return the record name field with the table prefix.
     *
     * @return string
     */
    public function getParentNameField(): string
    {
        return "d.Name";
    }

    /**
     * Return the place record ID field with the table prefix.
     *
     * @return string
     */
    public function getPlaceIDField(): string
    {
        return "d.CategoryID";
    }

    /**
     * Return the record type field with the table prefix.
     *
     * @return string
     */
    public function getPlaceRecordTypeField(): string
    {
        return "d.CategoryID";
    }

    /**
     * Join the record table for CommentModel::getID().
     *
     * @param Gdn_SQLDriver $query
     * @return void
     */
    public function joinParentTable(Gdn_SQLDriver &$query): void
    {
        $query->leftJoin("Discussion d", "c.DiscussionID = d.DiscussionID");
    }

    /**
     * Get the record fragments for a list of record IDs.
     *
     * @param array<int> $recordIDs
     * @return array
     * @throws Exception
     */
    public function getRecordFragments(array $recordIDs): array
    {
        $rows = $this->database
            ->createSql()
            ->select([
                "c.DiscussionID",
                "c.CommentID",
                "d.Name",
                "d.CategoryID",
                "c.InsertUserID",
                "c.DateInserted",
                "c.DateUpdated",
                "c.parentRecordType",
                "c.parentRecordID",
            ])
            ->from("Comment c")
            ->join("Discussion d", "c.DiscussionID = d.DiscussionID")
            ->where(["CommentID" => $recordIDs])
            ->get()
            ->resultArray();
        $fragments = [];
        foreach ($rows as $row) {
            $fragments[$row["CommentID"]] = [
                "name" => $row["Name"],
                "url" => \CommentModel::commentUrl($row),
                "dateUpdated" => $row["DateUpdated"] ?? $row["DateInserted"],
            ];
        }
        return $fragments;
    }

    /**
     * Get the trackable data of the parent record.
     *
     * @param int|array $recordOrRecordID
     * @return array
     */
    public function getTrackableData(int|array $recordOrRecordID): array
    {
        $trackableCommunityModel = Gdn::getContainer()->get(TrackableCommunityModel::class);
        try {
            $discussion = $trackableCommunityModel->getTrackableDiscussion($recordOrRecordID);
        } catch (\Exception $ex) {
            return [];
        }

        if ($discussion["discussionID"] == 0) {
            return [];
        }

        unset($discussion["discussionUser"], $discussion["record"], $discussion["commentMetric"]);

        return $discussion;
    }

    /**
     * @inheritdoc
     */
    public function isSearchable(): bool
    {
        return true;
    }
}

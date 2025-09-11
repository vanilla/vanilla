<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use CategoryModel;
use CommentModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Gdn_Database;
use Gdn_SQLDriver;
use Vanilla\Database\AlwaysBooleanExpression;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Models\AbstractCommentParentHandler;
use Vanilla\Utility\ModelUtils;

/**
 * Manage the interaction between escalations and comments.
 */
class EscalationCommentModel extends AbstractCommentParentHandler
{
    const RECORD_TYPE = "escalation";
    private ?EscalationModel $escalationModel = null;

    /**
     * @param Gdn_Database $database
     * @param CategoryModel $categoryModel
     */
    public function __construct(private Gdn_Database $database, private CategoryModel $categoryModel)
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
     * Validate if the current user has permission to view the comments of this escalation.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool
     * @throws NotFoundException
     * @throws ValidationException
     * @throws ServerException
     * @throws PermissionException
     */
    public function hasViewPermission(int $parentID, bool $throw = true): bool
    {
        return $this->getEscalationModel()->hasViewPermission($parentID, $throw);
    }

    /**
     * Test that the current user has the proper permissions to add comments to an escalation.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ServerException
     * @throws ValidationException
     */
    public function hasAddPermission(int $parentID, bool $throw = true): bool
    {
        return $this->getEscalationModel()->hasViewPermission($parentID, $throw);
    }

    /**
     * @inheritdoc
     */
    protected function hasCommentParentDeletePermissionBypass(array $commentRow): bool
    {
        // Throwing explicitly to make it not a bypass by a requirement.
        return $this->hasViewPermission($commentRow["parentRecordID"], true);
    }

    /**
     * @inheritdoc
     */
    protected function hasCommentParentEditPermissionBypass(array $commentRow): bool
    {
        // Throwing explicitly to make it not a bypass by a requirement.
        return $this->hasViewPermission($commentRow["parentRecordID"], true);
    }

    /**
     * Get the URL to a comment made to an escalation.
     *
     * @param array $comment
     *
     * @return string
     */
    public function getCommentUrlPath(array $comment): string
    {
        $commentID = val("CommentID", $comment) ?? val("commentID", $comment);
        $escalationID = val("parentRecordID", $comment);
        return "/dashboard/content/escalations/{$escalationID}?commentID={$commentID}";
    }

    /**
     * Increment the total comment count of an escalation.
     *
     * @param array $comment
     * @return void
     * @throws Exception
     */
    public function handleCommentInsert(array $comment): void
    {
        $escalationID = $comment["parentRecordID"];
        $this->getEscalationModel()->incrementCommentCount($escalationID);

        $this->getEscalationModel()->update(
            [
                "dateLastComment" => $comment["DateInserted"],
                "lastCommentID" => $comment["CommentID"],
                "lastCommentUserID" => $comment["InsertUserID"],
            ],
            ["escalationID" => $comment["parentRecordID"]]
        );
    }

    /**
     * Decrement the total comment count of an escalation.
     *
     * @param array $comment
     * @return void
     * @throws Exception
     */
    public function handleCommentDelete(array $comment): void
    {
        $escalationID = $comment["parentRecordID"];
        $this->getEscalationModel()->incrementCommentCount($escalationID, value: -1);

        // Recalculate the last comment date and user.
        $commentModel = Gdn::getContainer()->get(CommentModel::class);
        $lastComment = $commentModel->getLastCommentByParentRecordType($comment["parentRecordID"], "escalation") ?? [];
        $this->getEscalationModel()->update(
            [
                "dateLastComment" => $lastComment["DateInserted"] ?? null,
                "lastCommentID" => $lastComment["CommentID"] ?? null,
                "lastCommentUserID" => $lastComment["InsertUserID"] ?? null,
            ],
            ["escalationID" => $comment["parentRecordID"]]
        );
    }

    /**
     * Get a slot type based on the time since the post was escalated.
     *
     * @param int $parentID
     * @return string
     * @throws NotFoundException
     */
    public function getAutoSlotType(int $parentID): string
    {
        $sql = $this->database->sql();
        $dateInserted =
            $sql
                ->select("dateInserted")
                ->from("escalation")
                ->where("escalationID", $parentID)
                ->get()
                ->firstRow()->dateInserted ?? null;
        if ($dateInserted === null) {
            throw new NotFoundException("escalation", ["escalationID" => $parentID]);
        }
        return ModelUtils::getDateBasedSlotType($dateInserted);
    }

    /**
     * @inheritdoc
     */
    public function applyCommentQueryFiltering(Gdn_SQLDriver &$subQuery, array &$permissionWheres, array $where): void
    {
        $subQuery->leftJoin("escalation e", "c.parentRecordType = 'escalation' AND c.parentRecordID = e.escalationID");

        $parentRecordType = $where["c.parentRecordType"] ?? ($where["parentRecordType"] ?? null);
        $parentRecordID = $where["c.parentRecordID"] ?? ($where["parentRecordID"] ?? null);
        $escalationID = $parentRecordType === "escalation" ? $parentRecordID : null;

        $hasSingleRecordOptimization = $escalationID !== null && $this->hasViewPermission($escalationID, false);
        if ($hasSingleRecordOptimization) {
            $permissionWheres[] = new AlwaysBooleanExpression(true);
        } else {
            $escCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
                userID: Gdn::session()->UserID,
                permission: "Vanilla.Posts.Moderate"
            );
            $permissionWheres[] = [
                "e.placeRecordType" => "category",
                "e.placeRecordID" => $escCategoryIDs,
            ];
        }
    }

    /**
     * Fetch the parent record of a comment.
     *
     * @param int $parentID
     * @param bool $throw
     * @return array|false
     * @throws NotFoundException
     */
    public function getParentRecord(int $parentID, bool $throw = true): array|false
    {
        $escalation = $this->getEscalationModel()->getEscalation($parentID);

        if ($throw && $escalation === null) {
            throw new NotFoundException("Escalation", ["escalationID" => $parentID]);
        }
        return $escalation ?? false;
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
        $escalation = $this->getEscalationModel()->getEscalation($parentID);
        return $escalation["name"];
    }

    /**
     * Return the parent record category ID.
     *
     * @param int $parentID
     * @return int|null
     * @throws Exception
     */
    public function getCategoryID(int $parentID): ?int
    {
        $escalation = $this->getEscalationModel()->getEscalation($parentID);
        return $escalation["CategoryID"] ?? null;
    }

    /**
     * Return the record name field with the table prefix.
     *
     * @return string
     */
    public function getParentNameField(): string
    {
        return "e.name";
    }
    /**
     * Return the place record ID field with the table prefix.
     *
     * @return string
     */
    public function getPlaceIDField(): string
    {
        return "e.placeRecordID";
    }

    /**
     * Return the record type field with the table prefix.
     *
     * @return string
     */
    public function getPlaceRecordTypeField(): string
    {
        return "e.placeRecordType";
    }

    /**
     * Join the record table for CommentModel::getID().
     *
     * @param Gdn_SQLDriver $query
     * @return void
     */
    public function joinParentTable(Gdn_SQLDriver &$query): void
    {
        $query->leftJoin("escalation e", "c.parentRecordType = 'escalation' AND c.parentRecordID = e.escalationID");
    }

    /**
     * Get the escalation model as a singleton.
     *
     * @return EscalationModel
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function getEscalationModel(): EscalationModel
    {
        if ($this->escalationModel === null) {
            $this->escalationModel = Gdn::getContainer()->get(EscalationModel::class);
        }

        return $this->escalationModel;
    }

    /**
     * Get the trackable data of the escalation record.
     *
     * @param int|array $recordOrRecordID
     * @return array
     */
    public function getTrackableData(int|array $recordOrRecordID): array
    {
        if (is_array($recordOrRecordID)) {
            $recordID = $recordOrRecordID["escalationID"];
        } else {
            $recordID = $recordOrRecordID;
        }

        return $this->escalationModel->getTrackableData($recordID);
    }

    /**
     * These will need their own search implementation if we do them.
     * Search is not currently aware of `parentRecordType` on a comment so it always uses discussion.view permission
     * instead of posts.moderate.
     *
     * @inheritdoc
     */
    public function isSearchable(): bool
    {
        return false;
    }
}

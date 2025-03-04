<?php

namespace Vanilla\Forum\Models;

use Garden\Web\Exception\ClientException;
use Gdn_SQLDriver;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\PermissionCheckTrait;

/**
 * Manage the interaction between comments and their parent record.
 */
abstract class AbstractCommentParentHandler
{
    use PermissionCheckTrait;

    /**
     * Get the record type.
     *
     * @return string RecordType
     */
    abstract public function getRecordType(): string;

    /**
     * Validate if the current user has permission to view the comments of this parent.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool HasViewPermission
     */
    abstract public function hasViewPermission(int $parentID, bool $throw = true): bool;

    /**
     * Validate that the current user has permission to add comments to this parent.
     *
     * @param int $parentID
     * @param bool $throw
     * @return bool
     */
    abstract public function hasAddPermission(int $parentID, bool $throw = true): bool;

    /**
     * Validate that the current user has permission to edit comments to this parent.
     *
     * @param array $commentRow
     * @param bool $throw
     * @param int $timeLeft Out variable for the time left to edit.
     *
     * @return bool
     */
    public function hasEditPermission(array $commentRow, bool $throw = true, int &$timeLeft = 0): bool
    {
        return $this->runPermissionCallback($throw, function () use ($commentRow, &$timeLeft) {
            $this->permission("session.valid");

            $permissions = \Gdn::session()->getPermissions();
            if ($permissions->hasAny(["site.manage", "community.moderate"])) {
                return true;
            }

            // Some category specific bypasses
            if ($this->hasCommentParentEditPermissionBypass($commentRow)) {
                return true;
            }

            // We are not a moderator. At this point it has to be our comment.
            if ($commentRow["InsertUserID"] !== \Gdn::session()->UserID) {
                throw new PermissionException("comments.edit");
            }

            // Otherwise check the edit content timeout.
            if (!\CommentModel::editContentTimeout($commentRow, $timeLeft)) {
                throw new ClientException("Editing comments is not allowed..");
            }

            return true;
        });
    }

    /**
     * Given a comment row, check if we have a parent-specific edit permission bypass.
     *
     * @param array $commentRow
     * @return bool
     */
    abstract protected function hasCommentParentEditPermissionBypass(array $commentRow): bool;

    /**
     * Validate that the current user has permission to delete a comments of this parent.
     *
     * @param array $commentRow
     * @param bool $throw
     * @return bool
     */
    public function hasDeletePermission(array $commentRow, bool $throw = true): bool
    {
        return $this->runPermissionCallback($throw, function () use ($commentRow) {
            $this->permission("session.valid");
            $permissions = \Gdn::session()->getPermissions();
            if ($permissions->hasAny(["site.manage", "community.moderate"])) {
                return true;
            }

            if ($this->hasCommentParentDeletePermissionBypass($commentRow)) {
                return true;
            }

            $allowSelfDelete = \Gdn::config("Vanilla.Comments.AllowSelfDelete", false);
            $isOwnPost = $commentRow["InsertUserID"] === \Gdn::session()->UserID;
            if (!$allowSelfDelete || !$isOwnPost) {
                throw new PermissionException("comments.delete");
            }

            return true;
        });
    }

    /**
     * Given a comment row, check if we have a parent-specific delete permission bypass.
     *
     * @param array $commentRow
     * @return bool
     */
    abstract protected function hasCommentParentDeletePermissionBypass(array $commentRow): bool;

    /**
     * Get the URL of this comment.
     *
     * @param array $comment
     *
     * @return string CommentUrl
     */
    abstract public function getCommentUrlPath(array $comment): string;

    /**
     * Make the necessary adjustments when a comment is inserted under this parent.
     *
     * @param array $comment
     * @return void CommentID
     */
    abstract public function handleCommentInsert(array $comment): void;

    /**
     * Make the necessary adjustments to the parent when deleting one of its comment.
     *
     * @param array $comment
     * @return void
     */
    abstract public function handleCommentDelete(array $comment): void;

    /**
     * Get a slot type based on the time since the parent was created.
     *
     * @param int $parentID
     * @return string
     */
    abstract public function getAutoSlotType(int $parentID): string;

    /**
     * Fetch the parent record of a comment.
     *
     * @param int $parentID
     * @return array|false
     */
    abstract public function getParentRecord(int $parentID, bool $throw = true): array|false;

    /**
     * Fetch the parent record name of a comment.
     *
     * @param int $parentID
     * @return string
     */
    abstract public function getParentName(int $parentID): string;

    /**
     * Return the parent category ID.
     *
     * @param int $parentID
     * @return int|null
     */
    abstract public function getCategoryID(int $parentID): ?int;

    /**
     * Modify the query to filter comments based on the parent record.
     *
     * @param Gdn_SQLDriver $subQuery
     * @param array $permissionWheres
     * @param array $where
     *
     * @return void
     */
    abstract public function applyCommentQueryFiltering(
        Gdn_SQLDriver &$subQuery,
        array &$permissionWheres,
        array $where
    ): void;

    /**
     * Return the name field with the table prefix.
     *
     * @return string
     */
    abstract public function getParentNameField(): string;

    /**
     * Return the place ID field with the table prefix.
     *
     * @return string
     */
    abstract public function getPlaceIDField(): string;

    /**
     * Return the record type field with the table prefix.
     *
     * @return string
     */
    abstract public function getPlaceRecordTypeField(): string;

    /**
     * Join the table for CommentModel::getID().
     *
     * @param Gdn_SQLDriver $query
     * @return void
     */
    abstract public function joinParentTable(Gdn_SQLDriver &$query): void;

    /**
     * Get the trackable data of the parent record.
     *
     * @param int|array $recordOrRecordID
     * @return array
     */
    abstract public function getTrackableData(int|array $recordOrRecordID): array;

    /**
     * Determine if this commentParent type is searchable.
     *
     * @return bool
     */
    abstract public function isSearchable(): bool;
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use CommentModel;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Database\Select;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\SchemaUtils;

/**
 * Model for managing live records relating to reports and escalations.
 */
class CommunityManagementRecordModel
{
    private \Gdn_Database $db;
    private \DiscussionModel $discussionModel;
    private \CommentModel $commentModel;

    private \LogModel $logModel;

    /**
     * Constructor.
     */
    public function __construct(
        \Gdn_Database $db,
        \DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \LogModel $logModel
    ) {
        $this->db = $db;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->logModel = $logModel;
    }

    /**
     * Given a set of rows, join the live record data.
     *
     * @param array{recordType: string, recordID: int, placeRecordType: string, placeRecordID: int} $rows
     * @return array
     */
    public function joinLiveRecordData(array &$rows)
    {
        $recordIDsByType = [];

        foreach ($rows as $row) {
            $recordIDsByType[$row["recordType"]][] = $row["recordID"];
            $recordIDsByType[$row["placeRecordType"]][] = $row["placeRecordID"];
        }

        $recordIDsByType = array_map("array_unique", $recordIDsByType);
        $recordFragmentsByType = [];
        foreach ($recordIDsByType as $recordType => $recordIDs) {
            $recordFragmentsByType[$recordType] = $this->getRecordFragments($recordType, $recordIDs);
        }

        foreach ($rows as &$row) {
            $record = $recordFragmentsByType[$row["recordType"]][$row["recordID"]] ?? null;
            $row["recordUrl"] = $record["url"] ?? null;
            $row["recordIsLive"] = $record !== null;
            $row["recordWasEdited"] = $record !== null && dateCompare($record["dateUpdated"], $row["dateInserted"]) > 0;

            $placeRecord = $recordFragmentsByType[$row["placeRecordType"]][$row["placeRecordID"]] ?? null;
            $row["placeRecordUrl"] = $placeRecord["url"] ?? "/";
            $row["placeRecordName"] = $placeRecord["name"] ?? "Unknown";
        }

        return $rows;
    }

    /**
     * Given a recordType and an array of recordIDs get live record fragments.
     *
     * @param string $recordType
     * @param int[] $recordIDs
     *
     * @return array<array{name: string, url: string}>
     */
    private function getRecordFragments(string $recordType, array $recordIDs): array
    {
        switch ($recordType) {
            case "discussion":
                $rows = $this->db
                    ->createSql()
                    ->select(["DiscussionID", "Name", "CategoryID", "InsertUserID", "DateInserted", "DateUpdated"])
                    ->from("Discussion")
                    ->where(["DiscussionID" => $recordIDs])
                    ->get()
                    ->resultArray();
                $fragments = [];
                foreach ($rows as $row) {
                    $fragments[$row["DiscussionID"]] = [
                        "name" => $row["Name"],
                        "url" => \DiscussionModel::discussionUrl($row),
                        "dateUpdated" => $row["DateUpdated"] ?? $row["DateInserted"],
                    ];
                }
                return $fragments;
            case "comment":
                $parentName = $this->commentModel->getParentRecordField("getParentNameField");
                $parentPlaceID = $this->commentModel->getParentRecordField("getPlaceIDField");
                $parentPlaceRecordType = $this->commentModel->getParentRecordField("getPlaceRecordTypeField");

                $query = $this->db
                    ->createSql()
                    ->select([
                        "c.CommentID",
                        "c.InsertUserID",
                        "c.DateInserted",
                        "c.DateUpdated",
                        "c.parentRecordType",
                        "c.parentRecordID",
                    ])
                    ->select($parentName, "coalesce", "parentName")
                    ->select($parentPlaceID, "coalesce", "placeRecordID")
                    ->select($parentPlaceID, "coalesce", "CategoryID")
                    ->select($parentPlaceRecordType, "coalesce", "placeRecordType")
                    ->from("Comment c")
                    ->where(["CommentID" => $recordIDs]);

                $this->commentModel->addParentRecordTable($query);
                $rows = $query->get()->resultArray();
                $fragments = [];
                foreach ($rows as $row) {
                    $fragments[$row["CommentID"]] = [
                        "name" => $row["parentName"],
                        "url" => \CommentModel::commentUrl($row),
                        "dateUpdated" => $row["DateUpdated"] ?? $row["DateInserted"],
                    ];
                }
                return $fragments;
            case "category":
                $fragments = [];
                foreach ($recordIDs as $recordID) {
                    $category = \CategoryModel::categories($recordID);
                    if (!$category) {
                        continue;
                    }
                    $fragments[$recordID] = [
                        "name" => $category["Name"],
                        "url" => \CategoryModel::categoryUrl($category),
                        "dateUpdated" => $category["DateUpdated"] ?? $category["DateInserted"],
                    ];
                }
                return $fragments;
        }
        return [];
    }

    /**
     * Get a record assosciated with a report.
     *
     * @param string $recordType
     * @param int $recordID
     * @return array|false
     * @throws NotFoundException
     */
    public function getRecord(string $recordType, int $recordID)
    {
        switch ($recordType) {
            case "discussion":
                return $this->discussionModel->getID($recordID, DATASET_TYPE_ARRAY);
            case "comment":
                $comment = $this->commentModel->getID($recordID, DATASET_TYPE_ARRAY);
                if (!$comment) {
                    return $comment;
                }

                if ($comment["parentRecordType"] === "escalation") {
                    throw new ClientException("Escalation comments cannot be reported.");
                }

                $parentRecord = $this->commentModel->getParentRecord(
                    $comment["parentRecordID"],
                    $comment["parentRecordType"]
                );
                $categoryID = $this->commentModel->getCategoryIDByParentRecordType(
                    $comment["parentRecordID"],
                    $comment["parentRecordType"]
                );

                if (!$parentRecord) {
                    return $parentRecord;
                }
                return array_merge($comment, [
                    "Name" => CommentModel::generateCommentName($parentRecord["Name"]),
                    "CategoryID" => $categoryID,
                ]);

            default:
                throw new ClientException("Invalid record type.", 400);
        }
    }

    /**
     * Get a schema of record data for reports/escalations/triage.
     *
     * @return Schema
     */
    public static function minimalRecordSchema(): Schema
    {
        return Schema::parse([
            "recordType:s",
            "recordID:i?",
            "placeRecordType:s",
            "placeRecordID:i",
            "placeRecordUrl:s?",
            "placeRecordName:s?",
            "recordUrl:s?",
            "recordIsLive:b",
            "recordUserID:i",
            "recordDateInserted:dt",
        ]);
    }

    /**
     * Remove the escalated record.
     *
     * @param string $recordType
     * @param int $recordID
     * @return void
     * @throws \Exception
     */
    public function removeRecord(string $recordType, int $recordID): void
    {
        switch ($recordType) {
            case "discussion":
                $this->discussionModel->deleteID($recordID);
                break;
            case "comment":
                $this->commentModel->deleteID($recordID);
                break;
            default:
                throw new \Exception("Invalid record type.");
        }
    }

    /**
     * Restore the escalated record.
     *
     * @param string $recordType
     * @param int $recordID
     * @return void
     * @throws ClientException
     * @throws \Gdn_UserException
     */
    public function restoreRecord(string $recordType, int $recordID): void
    {
        $logRecord =
            $this->logModel->getWhere(
                ["RecordType" => $recordType, "RecordID" => $recordID, "Operation" => "Delete"],
                "LogID",
                "desc",
                0,
                1
            )[0] ?? null;
        if ($logRecord === null) {
            throw new ClientException("Could not locate the deleted record to restore.", 400, [
                "recordType" => $recordType,
                "recordID" => $recordID,
            ]);
        }

        $this->logModel->restore($logRecord, true);
    }

    /**
     * @return Schema
     */
    public static function fullRecordSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::minimalRecordSchema(),
            Schema::parse([
                "recordName:s",
                "recordFormat:s",
                "recordHtml:s?",
                "recordWasEdited:b",
                "recordExcerpt:s" => [
                    "minLength" => 0,
                ],
                "recordUserID:i",
                "recordUser?" => new UserFragmentSchema(),
                "recordDateInserted:dt",
                "recordDateUpdated:dt?",
                "recordStatus" => RecordStatusModel::getSchemaFragment(),
                "recordInternalStatus?" => RecordStatusModel::getSchemaFragment(),
            ])
        );
    }
}

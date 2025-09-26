<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Schema\Schema;
use Vanilla\AutomationRules\Triggers\StaleCollectionTrigger;
use Vanilla\Models\CollectionModel;
use Vanilla\Models\CollectionRecordModel;

class RemoveDiscussionFromTriggerCollectionAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "removeDiscussionFromTriggerCollectionAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Remove from trigger collection";
    }

    /**
     * @inheritdoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheritdoc
     */
    public static function getSchema(): Schema
    {
        return Schema::parse([]);
    }

    /**
     * @inheritdoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $schemaArray = $schema->getSchemaArray();
        unset(
            $schemaArray["properties"]["action"]["properties"]["actionValue"],
            $schemaArray["properties"]["action"]["required"][1]
        );
        $schema->offsetSet("properties", $schemaArray["properties"]);
    }

    /**
     * @inheritdoc
     */
    public static function getTriggers(): array
    {
        return [StaleCollectionTrigger::class];
    }

    /**
     * @inheritdoc
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $collectionRecord = \Gdn::getContainer()->get(CollectionRecordModel::class);
        $where = [
            "recordType" => "discussion",
            "collectionID" => $object["collectionID"],
            "recordID" => $object["recordID"],
        ];
        $logData = [
            "removeDiscussionFromCollection" => [
                "recordID" => $object["recordID"],
                "collectionID" => $object["collectionID"],
            ],
        ];
        $this->insertPostLog($object["recordID"], $logData);
        $collectionRecord->delete($where);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        $where["recordType"] = "discussion";
        return $where;
    }

    /**
     * Get post/patch schema for action value
     *
     * @return Schema
     */
    private static function getCollectionValidationSchema(): Schema
    {
        $collectionSchema = AddDiscussionToCollectionAction::getCollectionValidationSchema();
        // We do currently only support discussion type collections
        return $collectionSchema->merge(
            Schema::parse([
                "recordType:s?" => [
                    "enum" => ["discussion"],
                ],
            ])
        );
    }

    /**
     * @inheritdoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (
            isset($logData["removeDiscussionFromCollection"]) &&
            !empty($logData["removeDiscussionFromCollection"]["collectionID"])
        ) {
            $collectionModel = \Gdn::getContainer()->get(CollectionModel::class);
            $collection = $collectionModel->getCollectionRecordByID(
                $logData["removeDiscussionFromCollection"]["collectionID"]
            );
            $result .= "<div><b>" . t("Removed from collection") . ": </b>" . $collection["name"] . "</div>";
        }
        return $result;
    }
}

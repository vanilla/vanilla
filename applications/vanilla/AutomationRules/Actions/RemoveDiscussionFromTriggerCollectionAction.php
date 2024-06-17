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

class RemoveDiscussionFromTriggerCollectionAction extends AutomationAction implements AutomationActionInterface
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "removeDiscussionFromTriggerCollectionAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Remove discussion from trigger collection";
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        return Schema::parse([]);
    }

    /**
     * @inheridoc
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
     * @inheridoc
     */
    public static function getTriggers(): array
    {
        return [StaleCollectionTrigger::getType()];
    }

    /**
     * @inheridoc
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
        $this->insertTimedDiscussionLog($object["recordID"], $logData);
        $collectionRecord->delete($where);
        return true;
    }

    /**
     * @inheridoc
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
     * @inheritDoc
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

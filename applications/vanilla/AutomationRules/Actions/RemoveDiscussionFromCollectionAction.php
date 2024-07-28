<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Schema\Schema;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;
use Vanilla\Models\CollectionModel;

class RemoveDiscussionFromCollectionAction extends AutomationAction
{
    public string $affectedRecordType = "CollectionRecord";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "removeDiscussionFromCollectionAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Remove from collection";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $schema = [
            "collectionID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => true,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Collection to remove from", "Select one or more collections."),
                    new ApiFormChoices("/api/v2/collections", "/api/v2/collections/%s", "collectionID", "name"),
                    null,
                    true
                ),
            ],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public static function getTriggers(): array
    {
        return DiscussionRuleDataType::getTriggers();
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $collectionValueSchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => self::getCollectionValidationSchema(),
            ],
        ]);
        $schema->merge($collectionValueSchema);
    }

    /**
     * @inheridoc
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $collectionModel = \Gdn::getContainer()->get(CollectionModel::class);
        $record = [
            "recordType" => "discussion",
            "recordID" => $object["DiscussionID"],
        ];

        // Get all the collectionIDs that this record is already part of
        $recordCollections = $collectionModel->getCollectionsByRecord([
            "recordID" => $object["DiscussionID"],
            "recordType" => "discussion",
        ]);
        $recordCollectionIDs = array_column($recordCollections, "collectionID");
        $collectionsToRemove = array_intersect($recordCollectionIDs, $actionValue["collectionID"]);
        if (empty($collectionsToRemove)) {
            $this->logger->info("Skipped processing record as it is not part of the collections.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automationRules", "RemoveDiscussionFromCollectionAction"],
                "recordID" => $object["DiscussionID"],
                "collectionID" => $actionValue["collectionID"],
                "automationRuleID" => $this->getAutomationRuleID(),
                "dispatchUUID" => $this->getDispatchUUID(),
            ]);
            return false;
        }
        $logData = [
            "removeDiscussionFromCollection" => [
                "recordID" => $object["DiscussionID"],
                "collectionID" => array_values($collectionsToRemove),
            ],
        ];
        $collectionModel->removeRecordFromCollections($record, $actionValue["collectionID"]);
        $this->insertTimedDiscussionLog($object["DiscussionID"], $logData);
        return true;
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
            $collectionIDs = $logData["removeDiscussionFromCollection"]["collectionID"];

            $result .= "<div><b>" . t("Removed from collections") . ": </b>";

            $collectionModel = \Gdn::getContainer()->get(CollectionModel::class);
            $result .= $collectionModel->getCollectionNamesFromCollectionIDs($collectionIDs);
            $result .= "</div>";
        }
        return $result;
    }
}

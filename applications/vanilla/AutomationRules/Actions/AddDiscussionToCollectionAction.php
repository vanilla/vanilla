<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;
use Vanilla\Models\CollectionModel;
use Gdn;

class AddDiscussionToCollectionAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "addToCollectionAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Add to collection";
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
                    new FormOptions("Collection to add to", "Select one or more collections."),
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
     * Execute the long runner action
     *
     * @param array $actionValue Action value.
     * @param array $object Discussion DB object to perform action on.
     * @return bool
     * @throws ContainerException
     * @throws NotFoundException|NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $addedToCollection = false;
        $collectionModel = Gdn::getContainer()->get(CollectionModel::class);

        // Get all the collectionIDs that this record is already part of
        $recordCollections = $collectionModel->getCollectionsByRecord([
            "recordID" => $object["DiscussionID"],
            "recordType" => "discussion",
        ]);
        $recordCollectionIDs = array_column($recordCollections, "collectionID");
        $addedCollectionIDs = [];

        foreach ($actionValue["collectionID"] as $collectionID) {
            if (!in_array($collectionID, $recordCollectionIDs)) {
                $record = ["recordID" => $object["DiscussionID"], "recordType" => "discussion"];
                $collectionModel->addCollectionRecords($collectionID, [$record]);
                $addedToCollection = true;
                $addedCollectionIDs[] = $collectionID;
            } else {
                $this->logger->info("Record already part of collection", [
                    Logger::FIELD_TAGS => ["automationRules", "addDiscussionToCollection"],
                    "recordID" => $object["DiscussionID"],
                    "collectionID" => $collectionID,
                    "dispatchUUID" => $this->getDispatchUUID(),
                ]);
            }
        }
        // Only add to log if the record was added to a collection
        if ($addedToCollection) {
            $logData = [
                "addDiscussionToCollection" => [
                    "collectionID" => $addedCollectionIDs,
                    "recordID" => $object["DiscussionID"],
                ],
            ];
            $this->insertTimedDiscussionLog($object["DiscussionID"], $logData);
        }
        return $addedToCollection;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $collectionValueSchema = self::getCollectionValidationSchema();
        $addToCollectionSchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => $collectionValueSchema,
            ],
        ]);
        $schema->merge($addToCollectionSchema);
    }

    /**
     * Get validation schema for a collection Action.
     */
    public static function getCollectionValidationSchema(): Schema
    {
        return Schema::parse([
            "collectionID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
            ],
        ])->addValidator("collectionID", function (array $collectionIDs, ValidationField $field) {
            if (empty($collectionIDs)) {
                $field->addError("You should provide at least one collection to add.");
                return false;
            }
            if (!CollectionModel::checkCollectionsExist($collectionIDs)) {
                $field->addError("Invalid collection", [
                    "messageCode" => "Not all collections are valid.",
                    "code" => "403",
                ]);
                return Invalid::value();
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (
            isset($logData["addDiscussionToCollection"]) &&
            !empty($logData["addDiscussionToCollection"]["collectionID"])
        ) {
            $collectionIDs = $logData["addDiscussionToCollection"]["collectionID"];

            $result .= "<div><b>" . t("Added to collections") . ": </b>";

            $collectionModel = Gdn::getContainer()->get(CollectionModel::class);
            $result .= $collectionModel->getCollectionNamesFromCollectionIDs($collectionIDs);
            $result .= "</div>";
        }
        return $result;
    }
}

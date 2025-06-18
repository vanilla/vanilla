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
use Vanilla\Models\DiscussionInterface;

/**
 * Automation Action to add a discussion to a collection.
 */
class AddDiscussionToCollectionAction extends AutomationAction implements DiscussionInterface
{
    public string $affectedRecordType = "Discussion";

    private int $discussionID;

    private array $collectionIDs;

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "addDiscussionToCollectionAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Add Discussion To Collection";
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
     * @inheritdoc
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
        $this->setDiscussionID($object["DiscussionID"]);
        $this->setCollectionIDs($actionValue["collectionID"]);
        return $this->execute();
    }

    /**
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function execute(): bool
    {
        $addedToCollection = false;
        $collectionModel = Gdn::getContainer()->get(CollectionModel::class);

        // Get all the collectionIDs that this record is already part of
        $recordCollections = $collectionModel->getCollectionsByRecord([
            "recordID" => $this->getDiscussionID(),
            "recordType" => "discussion",
        ]);
        $recordCollectionIDs = array_column($recordCollections, "collectionID");
        $addedCollectionIDs = [];

        foreach ($this->getCollectionIDs() as $collectionID) {
            if (!in_array($collectionID, $recordCollectionIDs)) {
                $record = ["recordID" => $this->getDiscussionID(), "recordType" => "discussion"];
                $collectionModel->addCollectionRecords($collectionID, [$record]);
                $addedToCollection = true;
                $addedCollectionIDs[] = $collectionID;
            } else {
                $this->logger->info("Record already part of collection", [
                    Logger::FIELD_TAGS => ["automationRules", "addDiscussionToCollection"],
                    "recordID" => $this->getDiscussionID(),
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
                    "recordID" => $this->getDiscussionID(),
                ],
            ];

            $this->insertPostLog($this->getDiscussionID(), $logData);
        }
        return $addedToCollection;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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

    /**
     * @inheritdoc
     */
    public function setDiscussionID(int $discussionID): void
    {
        $this->discussionID = $discussionID;
    }

    /**
     * @inheritdoc
     */
    public function getDiscussionID(): int
    {
        return $this->discussionID;
    }

    /**
     * Set destination collection IDs.
     *
     * @param array $collectionID
     * @return void
     */
    private function setCollectionIDs(array $collectionID): void
    {
        $this->collectionIDs = $collectionID;
    }

    /**
     * Get destination collection IDs.
     *
     * @return array
     */
    private function getCollectionIDs(): array
    {
        return $this->collectionIDs;
    }
}

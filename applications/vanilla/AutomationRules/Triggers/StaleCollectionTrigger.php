<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use DateTimeImmutable;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromTriggerCollectionAction;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTriggerInterface;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use Vanilla\AutomationRules\Trigger\TimedAutomationTriggerInterface;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Models\CollectionModel;
use Vanilla\Models\CollectionRecordModel;
use Vanilla\Models\Model;

/**
 * Trigger class to handle stale records in a collection
 */
class StaleCollectionTrigger extends TimedAutomationTrigger implements
    AutomationTriggerInterface,
    TimedAutomationTriggerInterface
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "staleCollectionTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "A certain amount of time has passed since a post was added to a selected collection";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return [RemoveDiscussionFromTriggerCollectionAction::getType()];
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $schema = self::getTimeIntervalSchema();
        $schema["collectionID"] = [
            "type" => "array",
            "items" => [
                "type" => "integer",
            ],
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Collection to remove from", "Select one or more collections."),
                new ApiFormChoices("/api/v2/collections", "/api/v2/collections/%s", "collectionID", "name"),
                null,
                true
            ),
        ];

        return Schema::parse($schema);
    }

    /**
     * Get schema for trigger value
     *
     * @return Schema
     */
    public static function getTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(
            array_merge(self::getTimeIntervalParseSchema(), [
                "collectionID" => [
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                ],
            ])
        )->addValidator("collectionID", function (array $collectionIDs, ValidationField $field) {
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
        self::addTimedValidations($triggerSchema);
        return $triggerSchema;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $collectionSchema = Schema::parse([
            "trigger:o" => self::getTimedTriggerSchema(),
        ]);
        $schema->merge($collectionSchema);
        self::addActionTypeValidation($schema);
    }

    /**
     * @inheridoc
     */
    public function getObjectModel(): Model
    {
        return \Gdn::getContainer()->get(CollectionRecordModel::class);
    }

    /**
     * @inheridoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        return [
            "CollectionID" => $triggerValue["collectionID"],
            "DateInserted" => $dateRange,
        ];
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        return $this->getObjectModel()->getCount($where);
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        $whereOr = [];
        if (!empty($lastRecordId)) {
            $explode = explode("_", $lastRecordId);
            $whereOr = [
                [
                    "collectionID >=" => $explode[0],
                    "recordID >" => $explode[1],
                ],
                [
                    "collectionID >" => $explode[0],
                ],
            ];
        }
        return $this->getObjectModel()->getWhereIterator(
            $where,
            $whereOr,
            ["collectionID", "recordID"],
            "asc",
            AutomationRuleLongRunnerGenerator::BUCKET_SIZE
        );
    }
}

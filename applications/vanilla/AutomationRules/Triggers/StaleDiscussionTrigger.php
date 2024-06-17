<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CloseDiscussionAction;
use DateTimeImmutable;
use DiscussionModel;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromCollectionAction;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTriggerInterface;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use Vanilla\AutomationRules\Trigger\TimedAutomationTriggerInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;

/**
 * Class StaleDiscussionTrigger
 */
class StaleDiscussionTrigger extends TimedAutomationTrigger implements
    AutomationTriggerInterface,
    TimedAutomationTriggerInterface
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "staleDiscussionTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "A certain amount of time has passed since a post has been created but has not received any comments";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return [
            CloseDiscussionAction::getType(),
            BumpDiscussionAction::getType(),
            AddTagToDiscussionAction::getType(),
            MoveDiscussionToCategoryAction::getType(),
            AddDiscussionToCollectionAction::getType(),
            RemoveDiscussionFromCollectionAction::getType(),
        ];
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $formChoices = [];
        $enum = DiscussionModel::discussionTypes();
        foreach ($enum as $key => $value) {
            $formChoices[$value["apiType"]] = $key;
        }

        $schema = self::getTimeIntervalSchema();
        $schema["postType"] = [
            "type" => "array",
            "items" => [
                "type" => "string",
            ],
            "default" => array_keys($formChoices),
            "enum" => array_keys($formChoices),
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Post Type", "Select a post type."),
                new StaticFormChoices($formChoices),
                null,
                true
            ),
        ];

        return Schema::parse($schema);
    }

    /**
     * Get the trigger value schema
     *
     * @return Schema
     */
    public static function getTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(
            array_merge(self::getTimeIntervalParseSchema(), [
                "postType" => [
                    "type" => "array",
                    "items" => ["type" => "string"],
                    "nullable" => false,
                ],
            ])
        )->addValidator("postType", function ($postTypes, ValidationField $field) {
            $validPostTypes = array_values(array_filter(array_column(\DiscussionModel::discussionTypes(), "apiType")));
            $failed = false;
            if (!is_array($postTypes) || empty($postTypes)) {
                $failed = true;
            } else {
                foreach ($postTypes as $type) {
                    if (!in_array($type, $validPostTypes)) {
                        $failed = true;
                    }
                }
            }
            if ($failed) {
                $field->addError("Invalid post type, Valid post types are: " . json_encode($validPostTypes));
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
        $discussionCommentSchema = Schema::parse([
            "trigger:o" => self::getTimedTriggerSchema(),
        ]);
        $schema->merge($discussionCommentSchema);
        self::addActionTypeValidation($schema);
    }

    /**
     * @inheridoc
     */
    private function getPrimaryKey(): string
    {
        return "DiscussionID";
    }

    /**
     * @inheridoc
     */
    private function getObjectModel(): \Gdn_Model
    {
        return Gdn::getContainer()->get(DiscussionModel::class);
    }

    /**
     * @inheridoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        return [
            "Closed" => 0,
            "Type" => $triggerValue["postType"],
            "CountComments" => 0,
            "DateInserted" => $dateRange,
        ];
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        if (!empty($lastRecordId)) {
            $lastRecordId = (int) $lastRecordId;
            $where[$this->getPrimaryKey() . ">"] = $lastRecordId;
        }
        return $this->getObjectModel()->getWhereIterator(
            $where,
            "DiscussionID",
            "asc",
            false,
            AutomationRuleLongRunnerGenerator::BUCKET_SIZE
        );
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $sql = $this->getObjectModel()->SQL;
        // We need to ensure that NULL are treated as discussions.
        if (!empty($where["Type"]) && in_array("discussion", $where["Type"])) {
            $sql->beginWhereGroup()
                ->where("Type", $where["Type"])
                ->orWhere("Type is null")
                ->endWhereGroup();
            unset($where["Type"]);
        }
        return $sql->getCount("Discussion", $where);
    }
}

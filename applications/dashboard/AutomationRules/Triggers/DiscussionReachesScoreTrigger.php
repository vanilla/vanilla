<?php

/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use DiscussionModel;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Salesforce\Action\EscalateSalesforceCaseAction;
use Vanilla\Salesforce\Action\EscalateSalesforceLeadAction;

/**
 * Class DiscussionReachesScoreTrigger
 */
class DiscussionReachesScoreTrigger extends AutomationTrigger
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "discussionReachesScoreTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Post receives a minimum of points";
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
    public static function getActions(): array
    {
        return DiscussionRuleDataType::getActions();
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

        $schema = [
            "score:i" => [
                "required" => true,
                "minimum" => 1,
                "step" => 1,
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        "Points",
                        "Enter the total number of points the post should receive to trigger the rule.",
                        ""
                    ),
                    "number"
                ),
            ],
            "postType" => [
                "type" => "array",
                "required" => false,
                "items" => ["type" => "string"],
                "default" => array_keys($formChoices),
                "enum" => array_keys($formChoices),
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Post Type"),
                    new StaticFormChoices($formChoices),
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
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $postTypes = $discussionTypePointsSchema = Schema::parse([
            "trigger:o" => Schema::parse([
                "triggerType:s" => [
                    "enum" => [self::getType()],
                ],
                "triggerValue:o" => Schema::parse([
                    "score" => [
                        "type" => "integer",
                        "required" => true,
                    ],
                    "postType?" => [
                        "nullable" => true,
                        "type" => "array",
                        "items" => ["type" => "string"],
                    ],
                ])
                    ->addValidator("score", function ($points, ValidationField $field) {
                        if ($points <= 0) {
                            $field->addError("Points should be positive whole numbers greater than 0.");
                            return Invalid::value();
                        }
                    })
                    ->addValidator("postType", function ($postTypes, ValidationField $field) {
                        $validPostTypes = array_map("strtolower", array_keys(\DiscussionModel::discussionTypes()));
                        $failed = false;

                        foreach ($postTypes as $type) {
                            if (!in_array($type, $validPostTypes)) {
                                $failed = true;
                            }
                        }

                        if ($failed) {
                            $field->addError(
                                "Invalid post type, Valid post types are: " . json_encode($validPostTypes)
                            );
                            return Invalid::value();
                        }
                        return true;
                    }),
            ]),
        ]);

        $schema->merge($discussionTypePointsSchema);
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
    public function getWhereArray(array $triggerValue): array
    {
        $whereArray = [
            "Closed" => 0,
            "Score >=" => $triggerValue["score"],
        ];

        if (isset($triggerValue["postType"])) {
            $whereArray["Type"] = $triggerValue["postType"];
        }

        return $whereArray;
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $addNullToPostTypes = false;
        // Convert postTypes to lowercase
        $postType = array_map("strtolower", $where["Type"] ?? []);
        // If the postType contains `discussion` but not NULL, add NULL to the array.
        if (in_array("discussion", $postType) && !in_array(null, $postType)) {
            $addNullToPostTypes = true;
        }

        $sql = $this->getObjectModel()->SQL;
        // We need to ensure that NULL are treated as discussions.
        if (!empty($where["Type"]) && in_array("discussion", $where["Type"])) {
            $sqlWhere = $sql->beginWhereGroup();
            $sqlWhere->_whereIn("Type", $postType);
            if ($addNullToPostTypes) {
                $sqlWhere->orWhere("Type is null");
            }
            $sqlWhere->endWhereGroup();
            unset($where["Type"]);
        }
        return $sql->getCount("Discussion", $where);
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
}

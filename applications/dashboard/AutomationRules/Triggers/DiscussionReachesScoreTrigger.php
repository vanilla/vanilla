<?php

/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use CategoryModel;
use DiscussionModel;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use TagModel;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Dashboard\AutomationRules\Models\EscalationRuleDataType;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\PostTypeModel;

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
        return array_merge(DiscussionRuleDataType::getActions(), EscalationRuleDataType::getActions());
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
            "categoryID?" => [
                "required" => false,
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Category"),
                    new ApiFormChoices(
                        "/api/v2/categories/search?query=%s&limit=30",
                        "/api/v2/categories/%s",
                        "categoryID",
                        "name"
                    ),
                    null,
                    true
                ),
            ],
            "includeSubcategories?" => [
                "required" => false,
                "type" => "boolean",
                "x-control" => SchemaForm::checkBox(
                    new FormOptions(
                        "Include Subcategories",
                        "Include discussions from subcategories of the chosen category."
                    ),
                    new FieldMatchConditional(
                        "trigger.triggerValue",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "array",
                                "items" => ["type" => "integer"],
                                "minItems" => 1,
                            ],
                        ])
                    )
                ),
            ],
            "tagID?" => [
                "required" => false,
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Tags", "Select one or more tags"),
                    new ApiFormChoices("/api/v2/tags?type=User&limit=30&query=%s", "/api/v2/tags/%s", "tagID", "name"),
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
                    "categoryID?" => [
                        "type" => "array",
                        "items" => ["type" => "integer"],
                        "nullable" => true,
                    ],
                    "includeSubcategories:b?",
                    "tagID?" => [
                        "type" => "array",
                        "items" => ["type" => "integer"],
                        "nullable" => true,
                    ],
                ])
                    ->addValidator("score", function ($points, ValidationField $field) {
                        if ($points <= 0) {
                            $field->addError("Points should be positive whole numbers greater than 0.");
                            return Invalid::value();
                        }
                    })
                    ->addValidator("tagID", function (array $tagIDs, ValidationField $field) {
                        if (empty($tagIDs)) {
                            return true;
                        }
                        $tagModel = TagModel::instance();
                        foreach ($tagIDs as $tagID) {
                            if (!$tagModel->getID($tagID)) {
                                $field->addError("Invalid Tag", [
                                    "code" => 403,
                                    "messageCode" => "The tag {$tagID} is not a valid tag.",
                                ]);

                                return Invalid::value();
                            }
                        }
                        return true;
                    })
                    ->addValidator("categoryID", function (array $categoryIDs, ValidationField $field) {
                        if (empty($categoryIDs)) {
                            return true;
                        }
                        foreach ($categoryIDs as $categoryID) {
                            if (!CategoryModel::categories($categoryID)) {
                                $field->addError("Invalid Category", [
                                    "code" => 403,
                                    "messageCode" => "The category {$categoryID} is not a valid category.",
                                ]);

                                return Invalid::value();
                            }
                        }
                        return $categoryIDs;
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

        if (!empty($triggerValue["tagID"])) {
            $whereArray["TagID"] = $triggerValue["tagID"];
        }
        if (!empty($triggerValue["categoryID"])) {
            $categoryModel = CategoryModel::instance();
            $categoryIDs = $triggerValue["categoryID"];
            if ($triggerValue["includeSubcategories"] ?? false) {
                $categoryIDs = $categoryModel->getSearchCategoryIDs(null, null, true, null, $categoryIDs, true);
            }
            $whereArray["CategoryID"] = $categoryIDs;
        }

        return $whereArray;
    }

    /**
     * Format where array for the trigger query
     *
     * @param array $where
     * @return array
     */
    protected function formatWhere(array $where): array
    {
        $formattedWhere = [];
        if (!empty($where["TagID"])) {
            $formattedWhere["td.TagID"] = $where["TagID"];
            unset($where["TagID"]);
        }
        foreach ($where as $key => $value) {
            $formattedWhere["d." . $key] = $value;
        }
        return $formattedWhere;
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
        if (!empty($where["TagID"])) {
            $where = $this->formatWhere($where);
            $model = TagModel::instance();
            return $model->getTagDiscussionIterator(
                $where,
                "d." . $this->getPrimaryKey(),
                "asc",
                AutomationRuleLongRunnerGenerator::BUCKET_SIZE
            );
        } else {
            return $this->getObjectModel()->getWhereIterator(
                $where,
                $this->getPrimaryKey(),
                "asc",
                false,
                AutomationRuleLongRunnerGenerator::BUCKET_SIZE
            );
        }
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $sql = $this->getObjectModel()->SQL;

        if (!empty($where["Type"])) {
            PostTypeModel::whereParentPostType($sql, $where["Type"]);
            unset($where["Type"]);
        }
        if (!empty($where["TagID"])) {
            $where = $this->formatWhere($where);
            $sql->select("d.DiscussionID", "distinct")
                ->from("Discussion d")
                ->join("TagDiscussion td", "td.DiscussionID = d.DiscussionID")
                ->where($where);
            return $sql->get()->count();
        }

        return $sql->getCount("Discussion", $where);
    }
}

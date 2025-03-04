<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use DateTimeImmutable;
use DiscussionModel;
use CategoryModel;
use TagModel;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
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
 * Class StaleDiscussionTrigger
 */
class StaleDiscussionTrigger extends TimedAutomationTrigger
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
        return "Time since post has had no comments";
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
        $staleDiscussionSchema = [
            "postType" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                ],
                "required" => true,
                "default" => array_keys($formChoices),
                "enum" => array_keys($formChoices),
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Post Type", ""),
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
                    new FormOptions("Category", ""),
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
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => false,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Tag", "Select one or more tags"),
                    new ApiFormChoices("/api/v2/tags?type=User&limit=30&query=%s", "/api/v2/tags/%s", "tagID", "name"),
                    null,
                    true
                ),
            ],
            "additionalSettings" => self::getAdditionalSettingsSchema(),
        ];

        $schema = array_merge(self::getTimeIntervalSchema(), $staleDiscussionSchema);

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
                "categoryID:a?" => [
                    "items" => ["type" => "integer"],
                ],
                "includeSubcategories:b?",
                "tagID:a?" => [
                    "items" => ["type" => "integer"],
                ],
            ])
        )
            ->addValidator("postType", function ($postTypes, ValidationField $field) {
                $validPostTypes = array_values(
                    array_filter(array_column(\DiscussionModel::discussionTypes(), "apiType"))
                );
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
            })
            ->addValidator("categoryID", function ($categoryIDs, ValidationField $field) {
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
            ->addValidator("tagID", function ($tagIDs, ValidationField $field) {
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
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $categoryModel = CategoryModel::instance();
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        $where = [
            "Closed" => 0,
            "Type" => $triggerValue["postType"],
            "CountComments" => 0,
            "DateInserted" => $dateRange,
        ];
        if (!empty($triggerValue["tagID"])) {
            $where["TagID"] = $triggerValue["tagID"];
        }
        if (!empty($triggerValue["categoryID"])) {
            $categoryIDs = $triggerValue["categoryID"];
            if ($triggerValue["includeSubcategories"]) {
                $categoryIDs = $categoryModel->getSearchCategoryIDs(null, null, true, null, $categoryIDs, true);
            }
            $where["CategoryID"] = $categoryIDs;
        }

        return $where;
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
            $discussionModel = $this->getObjectModel();
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

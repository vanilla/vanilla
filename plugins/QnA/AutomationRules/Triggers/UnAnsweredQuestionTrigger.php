<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\AutomationRules\Triggers;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use CategoryModel;
use TagModel;
use QnaModel;
use DateTimeImmutable;
use Gdn;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\QnA\AutomationRule\Models\QuestionRuleDataType;

class UnAnsweredQuestionTrigger extends TimedAutomationTrigger
{
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "unAnsweredQuestionTrigger";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Time since question has been unanswered";
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
    public static function getActions(): array
    {
        return QuestionRuleDataType::getActions();
    }

    /**
     * @inheritdoc
     */
    public static function getSchema(): Schema
    {
        $schema = self::getTimeIntervalSchema();
        $schema["additionalSettings"] = self::getAdditionalSettingsSchema();
        $schema = array_merge($schema, [
            "categoryID?" => [
                "required" => false,
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Category", "Select a category"),
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
                        "Include questions from subcategories of the chosen category."
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
                    new ApiFormChoices(
                        "/api/v2/tags?type=User&limit=30&sort=name&query=%s",
                        "/api/v2/tags/%s",
                        "tagID",
                        "name"
                    ),
                    null,
                    true
                ),
            ],
        ]);

        return Schema::parse($schema);
    }

    /**
     * @inheritdoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(
            array_merge(self::getTimeIntervalParseSchema(), [
                "categoryID:a?" => [
                    "items" => ["type" => "integer"],
                ],
                "includeSubcategories:b?",
                "tagID:a?" => [
                    "items" => ["type" => "integer"],
                ],
            ])
        )
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
     * @inheritdoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $questionUnAnsweredSchema = Schema::parse([
            "trigger:o" => self::getTimedTriggerSchema(),
        ]);
        $schema->merge($questionUnAnsweredSchema);
    }

    /**
     * @inheritdoc
     */
    private function getPrimaryKey(string $alias = ""): string
    {
        $key = "DiscussionID";
        return $alias ? "$alias." . $key : $key;
    }

    /**
     * @inheritdoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $qnaModel = Gdn::getContainer()->get(QnaModel::class);
        $categoryModel = CategoryModel::instance();
        $unansweredStatuses = $qnaModel->getQuestionStatusByName([QnaModel::UNANSWERED, QnaModel::REJECTED]);
        $statusIDs = array_column($unansweredStatuses, "statusID");
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        $where = [
            "Type" => QnaModel::TYPE,
            "DateInserted" => $dateRange,
            "statusID" => $statusIDs,
        ];
        if (!empty($triggerValue["categoryID"])) {
            $categoryIDs = $triggerValue["categoryID"];
            if ($triggerValue["includeSubcategories"] ?? false) {
                $categoryIDs = $categoryModel->getSearchCategoryIDs(null, null, true, null, $categoryIDs, true);
            }
            $where["CategoryID"] = $categoryIDs;
        }
        if (!empty($triggerValue["tagID"])) {
            $where["TagID"] = $triggerValue["tagID"];
        }
        return $where;
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
     * @inheritdoc
     */
    public function getRecordsToProcess(mixed $lastRecordId, array $where): iterable
    {
        if (!empty($lastRecordId)) {
            $lastRecordId = (int) $lastRecordId;
            $where[$this->getPrimaryKey() . ">"] = $lastRecordId;
        }
        $where = $this->formatWhere($where);
        if (!empty($where["td.TagID"])) {
            $model = TagModel::instance();
            return $model->getTagDiscussionIterator(
                $where,
                $this->getPrimaryKey("d"),
                "asc",
                AutomationRuleLongRunnerGenerator::BUCKET_SIZE
            );
        } else {
            $model = \DiscussionModel::instance();
            return $model->getWhereIterator(
                $where,
                $this->getPrimaryKey("d"),
                "asc",
                false,
                AutomationRuleLongRunnerGenerator::BUCKET_SIZE
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        return $this->getUnAnsweredQuestionCount($this->formatWhere($where));
    }

    /**
     * Get record count to process for the trigger
     *
     * @param array $where
     * @return int
     */
    public function getUnAnsweredQuestionCount(array $where): int
    {
        $sql = Gdn::database()->sql();
        $sql->select("COUNT(DISTINCT(d.DiscussionID)) as count")->from("Discussion d");
        if (!empty($where["td.TagID"])) {
            $sql->join("TagDiscussion td", "d.DiscussionID = td.DiscussionID");
        }
        $result = $sql
            ->where($where)
            ->get()
            ->resultArray();
        return $result[0]["count"];
    }
}

<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use DiscussionModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Gdn;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;

class MoveDiscussionToCategoryAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "moveToCategoryAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Move post";
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
            "categoryID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => true,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Category to move to", "Select a category"),
                    new ApiFormChoices(
                        "/api/v2/categories/search?query=%s&limit=30",
                        "/api/v2/categories/%s",
                        "categoryID",
                        "name"
                    )
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
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussion = $discussionModel->getID($object["DiscussionID"], DATASET_TYPE_ARRAY);
        $currentCategoryID = $discussion["CategoryID"];
        if ($currentCategoryID === $actionValue["categoryID"]) {
            return false;
        }
        $discussionModel->moveDiscussion($object["DiscussionID"], $actionValue["categoryID"], true);
        $logData = [
            "moveDiscussion" => [
                "recordID" => $object["DiscussionID"],
                "fromCategoryID" => $currentCategoryID,
                "toCategoryID" => $actionValue["categoryID"],
            ],
        ];
        $this->insertPostLog($object["DiscussionID"], $logData);
        return true;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $categoryValueSchema = Schema::parse([
            "categoryID" => [
                "type" => "integer",
            ],
        ])->addValidator("categoryID", function (int $categoryID, ValidationField $field) {
            if ($categoryID === 0) {
                $field->addError("You should provide a category to move discussion.");
                return false;
            }
            if (!\CategoryModel::categories($categoryID)) {
                $field->addError("Invalid category", [
                    "messageCode" => "The category {$categoryID} is not a valid category.",
                    "code" => "403",
                ]);
                return Invalid::value();
            }
        });

        $addCategorySchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => $categoryValueSchema,
            ],
        ]);
        $schema->merge($addCategorySchema);
    }

    /**
     * @inheridoc
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        $where["CategoryID <>"] = $actionValue["categoryID"];
        return $where;
    }

    /**
     * @inheritDoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div><div>";
        if (isset($logData["moveDiscussion"]) && isset($logData["moveDiscussion"]["fromCategoryID"])) {
            $categoryData = \CategoryModel::categories($logData["moveDiscussion"]["fromCategoryID"]);
            $result .= "<div><b>" . t("Moved from category") . ": </b>" . $categoryData["Name"] . "</div>";
        }
        if (isset($logData["moveDiscussion"]) && isset($logData["moveDiscussion"]["toCategoryID"])) {
            $categoryData = \CategoryModel::categories($logData["moveDiscussion"]["toCategoryID"]);
            $result .= "<div><b>" . t("Moved to category") . ": </b>" . $categoryData["Name"] . "</div>";
        }

        $result .= "</div>";
        return $result;
    }
}

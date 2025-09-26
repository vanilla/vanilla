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
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Models\DiscussionInterface;

/**
 * Automation rule action to move a discussion to a specific category.
 */
class MoveDiscussionToCategoryAction extends AutomationAction implements DiscussionInterface
{
    public string $affectedRecordType = "Discussion";

    private int $discussionID;

    private int $categoryID;

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "moveToCategoryAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Move post";
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
            "categoryID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => true,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Category to move to",
                        "Category settings are respected by automation rules. Posts will only be moved into categories that accept that post type."
                    ),
                    new ApiFormChoices(
                        "/api/v2/categories/search?query=%s&limit=30&displayAs[]=Discussions",
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
     * @throws ClientException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $this->setDiscussionID($object["DiscussionID"]);
        $this->setCategoryID($actionValue["categoryID"]);

        return $this->execute();
    }

    /**
     * Execute the action.
     *
     * @return bool
     * @throws ClientException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws NoResultsException
     */
    public function execute(): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussion = $discussionModel->getID($this->getDiscussionID(), DATASET_TYPE_ARRAY);
        $currentCategoryID = $discussion["CategoryID"];
        if ($currentCategoryID === $this->getCategoryID()) {
            return false;
        }
        $discussionModel->moveDiscussion($this->getDiscussionID(), $this->getCategoryID(), true);
        $logData = [
            "moveDiscussion" => [
                "recordID" => $this->getDiscussionID(),
                "fromCategoryID" => $currentCategoryID,
                "toCategoryID" => $this->categoryID,
            ],
        ];
        $this->insertPostLog($this->getDiscussionID(), $logData);
        return true;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        $where["CategoryID <>"] = $actionValue["categoryID"];
        return $where;
    }

    /**
     * @inheritdoc
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
     * Set category ID.
     *
     * @param int $categoryID
     * @return void
     */
    private function setCategoryID(int $categoryID): void
    {
        $this->categoryID = $categoryID;
    }

    /**
     * Get category ID.
     *
     * @return int
     */
    private function getCategoryID(): int
    {
        return $this->categoryID;
    }
}

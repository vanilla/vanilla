<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use CategoryModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\AutomationRules\Models\UserInterface;
use Vanilla\Dashboard\AutomationRules\Models\UserRuleDataType;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;

/**
 * Action class for following a specific category
 */
class UserFollowCategoryAction extends AutomationAction implements UserInterface, EventActionInterface
{
    private int $userID;

    public string $affectedRecordType = "User";

    private CategoryModel $categoryModel;
    private UserNotificationPreferencesModel $userNotificationPreferencesModel;

    /**
     * @param int $automationRuleID
     * @param string $dispatchType
     * @param string|null $dispatchUUID
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws NoResultsException
     */
    public function __construct(
        int $automationRuleID,
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_TRIGGERED,
        ?string $dispatchUUID = null
    ) {
        parent::__construct($automationRuleID, $dispatchType, $dispatchUUID);
        $this->categoryModel = \Gdn::getContainer()->get(CategoryModel::class);
        $this->userNotificationPreferencesModel = \Gdn::getContainer()->get(UserNotificationPreferencesModel::class);
    }

    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "categoryFollowAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Follow category";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "users";
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
                    new FormOptions("Category to Follow", "Select one or more categories to follow"),
                    new ApiFormChoices(
                        "/api/v2/categories/search?query=%s&limit=30&displayAs[]=Discussions",
                        "/api/v2/categories/%s",
                        "categoryID",
                        "name"
                    ),
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
        return UserRuleDataType::getTriggers();
    }

    /**
     * Set userID
     *
     * @param int $userID
     * @return void
     */
    public function setUserID(int $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * Get userID
     *
     * @return int
     */
    public function getUserID(): int
    {
        return $this->userID;
    }

    /**
     * Get user data
     *
     * @return array
     * @throws Exception
     */
    public function getUserData(): array
    {
        $userData = \Gdn::userModel()->getID($this->getUserID(), DATASET_TYPE_ARRAY);
        if (empty($userData)) {
            throw new Exception("User not found");
        }
        return $userData;
    }

    /**
     * @inheridoc
     */
    public function execute(): bool
    {
        $categoryFollowRule = $this->getAutomationRule();
        if ($categoryFollowRule["actionType"] !== self::getType()) {
            $this->logger->error("Invalid recipe received  for " . self::getType(), [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automation rules", "categoryFollowAction"],
                "automationRuleID" => $this->getAutomationRuleID(),
            ]);
            throw new Exception("Invalid Recipe received for " . self::getType());
        }
        $userId = $this->getUserID();
        if (empty($userId)) {
            throw new NotFoundException("User ID is not set");
        }
        if ($this->getUserData()) {
            $logData = [];
            $followCategoryIDs = $categoryFollowRule["action"]["actionValue"]["categoryID"];
            $currentlyFollowedCategories = $this->categoryModel->getFollowed($userId);
            if (!empty($currentlyFollowedCategories)) {
                $currentlyFollowedCategoryIds = array_keys($currentlyFollowedCategories);
                $followCategoryIDs = array_diff($followCategoryIDs, $currentlyFollowedCategoryIds);
                // If the user already follows every categories set for assignment, we don't need to do anything.
                if (empty($followCategoryIDs)) {
                    $this->logger->info("Skipped Processing, as the user already follows the targeted categories", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                        Logger::FIELD_TAGS => ["automation rules", "categoryFollowAction"],
                        "automationRuleID" => $this->getAutomationRuleID(),
                        "actionCategories" => $followCategoryIDs,
                        "currentlyFollowedCategories" => $currentlyFollowedCategoryIds,
                        "userID" => $userId,
                    ]);
                    return false;
                }
                $logData["Data"]["currentlyFollowedCategories"] = $currentlyFollowedCategoryIds;
            }
            $followedCategories = [];
            $errorMessage = [];
            // LogData
            $logData["AutomationRuleRevisionID"] = $categoryFollowRule["automationRuleRevisionID"];
            $logData["RecordType"] = "UserCategory";
            $logData["RecordUserID"] = $userId;
            $userPreferences = $this->userNotificationPreferencesModel->getUserPrefs($userId);
            $categoryPreference = $this->categoryModel->getCategoryPreferences();
            $preferences = [];
            foreach ($categoryPreference as $key => $value) {
                if ($key === $this->categoryModel::OUTPUT_PREFERENCE_FOLLOW) {
                    $preferences[$value] = true;
                } else {
                    $userPreferenceKey = str_replace("Preferences.", "", $value);
                    $preferences[$value] = $userPreferences[$userPreferenceKey] ?? false;
                }
            }
            foreach ($followCategoryIDs as $categoryID) {
                try {
                    // This can rarely result in an error if the category is deleted or any permissions on the category is changed.
                    $this->categoryModel->setPreferences($userId, $categoryID, $preferences);
                    $followedCategories[] = $categoryID;
                } catch (\Throwable $e) {
                    // Don't throw the exception, just log it
                    $errorMessage[$categoryID] = $e->getMessage();
                    if ($e->getPrevious()) {
                        $errorMessage[$categoryID] .= ", " . $e->getPrevious()->getMessage();
                    }
                    continue;
                }
            }
            $logData["Data"]["newFollowedCategories"] = $followedCategories;
            if (!empty($errorMessage)) {
                $dispatchStatus = !empty($followedCategories)
                    ? AutomationRuleDispatchesModel::STATUS_WARNING
                    : AutomationRuleDispatchesModel::STATUS_FAILED;
                $this->dispatch($errorMessage, $dispatchStatus);
            } else {
                //This is a success
                $this->dispatch();
            }
            $logData["DispatchUUID"] = $this->getDispatchUUID();
            $this->insertLogEntry($logData);
            if ($this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED) {
                $this->automationRuleDispatchesModel->updateDateFinished($logData["DispatchUUID"]);
            }
        }
        return true;
    }

    /**
     * Dispatch and log the data
     *
     * @param array $errorMessages
     * @param string $dispatchStatus
     * @return void
     */
    private function dispatch(
        array $errorMessages = [],
        string $dispatchStatus = AutomationRuleDispatchesModel::STATUS_SUCCESS
    ): void {
        // If not dispatched and the dispatch type is triggered
        if (!$this->dispatched && $this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED) {
            $dispatchErrorMessages = null;
            if (!empty($errorMessages)) {
                $lastCategoryID = array_key_last($errorMessages);
                $dispatchErrorMessages = "";
                foreach ($errorMessages as $categoryID => $errorMessage) {
                    $this->logger->error("Error occurred trying to follow category", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                        Logger::FIELD_TAGS => ["automation rules", "categoryFollowAction"],
                        "automationRuleID" => $this->getAutomationRuleID(),
                        "automationRuleDispatchUUID" => $this->getDispatchUUID(),
                        "categoryID" => $categoryID,
                        "error" => $errorMessage,
                    ]);
                    $dispatchErrorMessages .= "CategoryID: $categoryID - " . $errorMessage;
                    if ($categoryID !== $lastCategoryID) {
                        $dispatchErrorMessages .= ", ";
                    }
                }
            }
            $attributes = [
                "affectedRecordType" => "User",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => $dispatchStatus !== AutomationRuleDispatchesModel::STATUS_FAILED ? 1 : 0,
            ];
            $this->logDispatched($dispatchStatus, $dispatchErrorMessages, $attributes);
        }
    }

    /**
     * @inheridoc
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $userID = $object["recordID"] ?? ($object["userID"] ?? $object["UserID"]);
        $this->setUserID($userID);
        return $this->execute();
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $categoryValueSchema = Schema::parse([
            "categoryID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
            ],
        ])->addValidator("categoryID", function (array $followedCategories, ValidationField $field) {
            if (empty($followedCategories)) {
                $field->addError("You should provide at least one category to follow.");
                return false;
            }
            foreach ($followedCategories as $categoryID) {
                if (!\CategoryModel::categories($categoryID)) {
                    $field->addError("Invalid category", [
                        "messageCode" => "The category {$categoryID} is not a valid category.",
                        "code" => "403",
                    ]);
                    return Invalid::value();
                }
            }
        });

        $followedCategorySchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => $categoryValueSchema,
            ],
        ]);
        $schema->merge($followedCategorySchema);
    }

    /**
     * @inheritDoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div><div>";
        if (isset($logData["currentlyFollowedCategories"])) {
            $result .= "<p><b>" . t("Followed Categories") . ":</b>";
            foreach ($logData["currentlyFollowedCategories"] as $categoryID) {
                $categoryData = CategoryModel::categories($categoryID);
                $result .= " " . $categoryData["Name"];
            }
            $result .= "</p>";
        }
        if (isset($logData["newFollowedCategories"])) {
            $result .= "<b>" . t("Newly Followed Categories") . ": </b>";
            foreach ($logData["newFollowedCategories"] as $index => $categoryID) {
                $categoryData = CategoryModel::categories($categoryID);
                $isLastOrOnlyItem =
                    count($logData["newFollowedCategories"]) === 1 ||
                    (count($logData["newFollowedCategories"]) > 1 &&
                        $index === count($logData["newFollowedCategories"]) - 1);
                $result .= $categoryData["Name"] . ($isLastOrOnlyItem ? " " : ", ");
            }
        }
        $result .= "</div>";
        return $result;
    }
}

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
use Gdn_UserException;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use TagModel;
use Vanilla\Logger;
use Vanilla\Models\DiscussionInterface;

/**
 * Automation Action to add a tag to a discussion.
 */
class AddTagToDiscussionAction extends AutomationAction implements DiscussionInterface
{
    private int $discussionID;

    private array $actionValue = [];

    public string $affectedRecordType = "Discussion";
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "addTagAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Add tag";
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
            "tagID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => true,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Tags to add", "Select one or more tags"),
                    new ApiFormChoices("/api/v2/tags?type=User&limit=30&query=%s", "/api/v2/tags/%s", "tagID", "name"),
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
     * @throws NotFoundException
     * @throws Gdn_UserException
     * @throws NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $this->setActionValue($actionValue);
        $this->setDiscussionID($object["DiscussionID"]);
        return $this->execute();
    }

    /**
     * Execute the action.
     *
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException|Gdn_UserException
     */
    public function execute(): bool
    {
        $tagIDs = $this->getActionValue("tagID");

        $tagModel = \Gdn::getContainer()->get(TagModel::class);
        // Make sure the tags are not already added to the discussion
        $currentDiscussionTagIDs = [];
        $currentDiscussionTags = $tagModel->getDiscussionTags($this->getDiscussionID(), TagModel::IX_TAGID);
        if (!empty($currentDiscussionTags)) {
            $currentDiscussionTagIDs = array_keys($currentDiscussionTags);
        }
        $tagsToApply = array_diff($tagIDs, $currentDiscussionTagIDs);
        if (empty($tagsToApply)) {
            $this->logger->info("No tags to apply for discussion.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automation rules", "addTagsToDiscussion"],
                "discussionID" => $this->getDiscussionID(),
                "automationRuleID" => $this->getAutomationRuleID(),
                "dispatchUUID" => $this->getDispatchUUID(),
            ]);
            return false;
        }
        $tagModel->addDiscussion($this->getDiscussionID(), $tagIDs);
        $logData = [
            "addTagToDiscussion" => [
                "tagID" => array_values($tagsToApply),
                "recordID" => $this->getDiscussionID(),
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
        $tagValueSchema = Schema::parse([
            "tagID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "required" => true,
            ],
        ])->addValidator("tagID", function (array $tagIDs, ValidationField $field) {
            if (empty($tagIDs)) {
                $field->addError("You should provide at least one tag to add.");
                return false;
            }
            if (!\TagModel::validateTagIDsExist($tagIDs)) {
                $field->addError("Invalid tag", [
                    "messageCode" => "Not all tags are valid.",
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
                "actionValue:o" => $tagValueSchema,
            ],
        ]);
        $schema->merge($addCategorySchema);
    }

    /**
     * @inheritdoc
     */
    public function expandLogData(array $logData): string
    {
        $tagModel = \Gdn::getContainer()->get(TagModel::class);
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (!empty($logData["addTagToDiscussion"])) {
            $tagIDs = $logData["addTagToDiscussion"]["tagID"];
            $tagData = $tagModel->getTagsByIDs($tagIDs);
            $result .= "<div><b>" . t("Tags Added") . ": </b>";

            foreach ($tagData as $index => $tag) {
                $isLastOrOnlyItem = count($tagData) === 1 || (count($tagData) > 1 && $index === count($tagData) - 1);
                $result .= $tag["Name"] . ($isLastOrOnlyItem ? " " : ", ");
            }
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
     * `actionValue` setter function.
     *
     * @param array $actionValue
     * @return void
     */
    public function setActionValue(array $actionValue): void
    {
        $this->actionValue = $actionValue;
    }

    /**
     * `actionValue` getter function.
     *
     * @param string $key
     * @return array
     */
    private function getActionValue(string $key = ""): array
    {
        if ($key != "") {
            return $this->actionValue[$key] ?? [];
        }
        return $this->actionValue;
    }
}

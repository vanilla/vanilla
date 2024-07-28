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
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use TagModel;
use Vanilla\Logger;

class AddTagToDiscussionAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "addTagAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Add tag";
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
            "tagID" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
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
     * @return bool
     * @throws ContainerException
     * @throws NotFoundException
     * @throws \Gdn_UserException
     * @throws NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $tagModel = \Gdn::getContainer()->get(TagModel::class);
        // Make sure the tags are not already added to the discussion
        $currentDiscussionTagIDs = [];
        $tagsToApply = [];
        $currentDiscussionTags = $tagModel->getDiscussionTags($object["DiscussionID"], TagModel::IX_TAGID);
        if (!empty($currentDiscussionTags)) {
            $currentDiscussionTagIDs = array_keys($currentDiscussionTags);
        } else {
            $currentDiscussionTagIDs = [];
        }
        $tagsToApply = array_diff($actionValue["tagID"], $currentDiscussionTagIDs);
        if (empty($tagsToApply)) {
            $this->logger->info("No tags to apply for discussion.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automation rules", "addTagsToDiscussion"],
                "discussionID" => $object["DiscussionID"],
                "automationRuleID" => $this->getAutomationRuleID(),
                "dispatchUUID" => $this->getDispatchUUID(),
            ]);
            return false;
        }
        $tagModel->addDiscussion($object["DiscussionID"], $actionValue["tagID"]);
        $logData = [
            "addTagToDiscussion" => [
                "tagID" => array_values($tagsToApply),
                "recordID" => $object["DiscussionID"],
            ],
        ];
        $this->insertTimedDiscussionLog($object["DiscussionID"], $logData);
        return true;
    }

    /**
     * @inheridoc
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
     * @inheritDoc
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
}

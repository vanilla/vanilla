<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use DiscussionModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Gdn;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\DiscussionInterface;

/**
 * Automation Action to bump a discussion.
 */
class BumpDiscussionAction extends AutomationAction implements DiscussionInterface
{
    public string $affectedRecordType = "Discussion";
    private int $discussionID;
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "bumpDiscussionAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Bump post";
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
        return Schema::parse([]);
    }

    /**
     * @inheritdoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $schemaArray = $schema->getSchemaArray();
        unset(
            $schemaArray["properties"]["action"]["properties"]["actionValue"],
            $schemaArray["properties"]["action"]["required"][1]
        );
        $schema->offsetSet("properties", $schemaArray["properties"]);
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
     * @throws NotFoundException|NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $this->setDiscussionID($object["DiscussionID"]);
        return $this->execute();
    }

    /**
     * Execute the action.
     *
     * @return bool
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function execute(): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussionModel->setField($this->getDiscussionID(), "DateLastComment", CurrentTimeStamp::getMySQL());

        // Log the action
        $logData = [
            "bumpDiscussion" => [
                "recordID" => $this->getDiscussionID(),
            ],
        ];

        if (!$this->dispatched) {
            $attributes = [
                "affectedRecordType" => "Discussion",
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 0,
            ];
            $this->logDispatched(AutomationRuleDispatchesModel::STATUS_RUNNING, null, $attributes);
        }
        $this->insertPostLog($this->getDiscussionID(), $logData);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        $where["DateLastComment IS NOT NULL"] = "";
        return $where;
    }

    /**
     * @inheritdoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (isset($logData["bumpDiscussion"])) {
            $result .= "<div>Bump discussion</div>";
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
}

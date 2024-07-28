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
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;

class BumpDiscussionAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "bumpDiscussionAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Bump post";
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
        return Schema::parse([]);
    }

    /**
     * @inheridoc
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
     * @throws NotFoundException|NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussionModel->setField($object["DiscussionID"], "DateLastComment", CurrentTimeStamp::getMySQL());
        $logData = [
            "bumpDiscussion" => [
                "recordID" => $object["DiscussionID"],
            ],
        ];
        $this->insertTimedDiscussionLog($object["DiscussionID"], $logData);
        return true;
    }

    /**
     * @inheridoc
     */
    public function addWhereArray(array $where, array $actionValue): array
    {
        $where["DateLastComment IS NOT NULL"] = "";
        return $where;
    }

    /**
     * @inheritDoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (isset($logData["bumpDiscussion"])) {
            $result .= "<div>Bump discussion</div>";
        }
        return $result;
    }
}

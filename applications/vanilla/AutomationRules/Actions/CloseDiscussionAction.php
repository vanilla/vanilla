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
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\Exception\Database\NoResultsException;

class CloseDiscussionAction extends AutomationAction
{
    public string $affectedRecordType = "Discussion";
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "closeDiscussionAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Close post";
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
     * @throws NotFoundException
     * @throws NoResultsException
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussionModel->closeDiscussion($object["DiscussionID"]);
        $logData = [
            "closeDiscussion" => [
                "recordID" => $object["DiscussionID"],
            ],
        ];
        $this->insertPostLog($object["DiscussionID"], $logData);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (isset($logData["closeDiscussion"])) {
            $result .= "<div>Close discussion</div>";
        }
        return $result;
    }
}

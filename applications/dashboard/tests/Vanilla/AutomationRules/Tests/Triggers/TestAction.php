<?php

use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\CurrentTimeStamp;

class TestAction extends BumpDiscussionAction
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "testAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Test the action";
    }

    /**
     * @inheridoc
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        //This should throw an exception
        if (empty($actionValue)) {
            $discussionModel->getWhere(["InvalidField" => "InvalidValue"]);
        } else {
            throw new Error("this has failed", 500);
        }

        return true;
    }
}

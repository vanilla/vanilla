<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace Vanilla\Forum\Addon;

use Garden\EventHandlersInterface;
use Psr\Log\LoggerInterface;
use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\Dashboard\Addon\AbstractProfileFieldEventHandler;

// Needed because special classes are loaded before the autoloader is ready.
require_once PATH_APPLICATIONS . "/dashboard/Addon/AbstractProfileFieldEventHandler.php";
/**
 * Automation rule event that captures profile fields update and triggers the category follow action.
 */
class ProfileFieldUserFollowCategoryEventHandlers extends AbstractProfileFieldEventHandler implements
    EventHandlersInterface
{
    /**
     * D.I.
     */
    public function __construct(LoggerInterface $log)
    {
        parent::__construct($log);
        $this->actionType = UserFollowCategoryAction::getType();
    }

    /**
     * Validate to see if we have proper data
     *
     * @param array $revisionData
     * @return bool
     */
    protected function validateRuleValues(array $revisionData): bool
    {
        if (
            !is_array($revisionData["triggerValue"]["profileField"]) ||
            empty($revisionData["actionValue"]["categoryID"])
        ) {
            return false;
        }
        return true;
    }
}

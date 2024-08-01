<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Garden\EventHandlersInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;

// Needed because special classes are loaded before the autoloader is ready.
require_once __DIR__ . "/AbstractProfileFieldEventHandler.php";
/**
 * Recipe event that captures profile fields update and triggers the modify role action.
 */
class ProfileFieldAddRemoveRoleEventHandlers extends AbstractProfileFieldEventHandler implements EventHandlersInterface
{
    /**
     * D.I.
     */
    public function __construct(LoggerInterface $log)
    {
        parent::__construct($log);
        $this->actionType = AddRemoveUserRoleAction::getType();
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
            empty($revisionData["actionValue"]["addRoleID"])
        ) {
            return false;
        }
        return true;
    }
}

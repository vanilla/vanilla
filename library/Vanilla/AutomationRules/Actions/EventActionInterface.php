<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

/**
 * EventActionInterface
 */
interface EventActionInterface
{
    /**
     * Execute the action
     * @return bool
     */
    public function execute(): bool;
}

<?php
/**
 * @author David Barbier <dbarbier@faenix.ca>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

/**
 * Interface UserInterface
 */
interface UserInterface
{
    /**
     * Get the user id
     *
     * @return int
     */
    public function getUserID(): int;

    /**
     * Set the user id
     */
    public function setUserID(int $userID): void;
}

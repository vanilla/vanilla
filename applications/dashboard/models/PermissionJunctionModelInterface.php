<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Interface PermissionJunctionModelInterface
 */
interface PermissionJunctionModelInterface
{
    /**
     * Notify model about permissions getting updated. Useful for clearing caches.
     */
    public function onPermissionChange(): void;

    /**
     * Get junction aliases.
     *
     * @return array
     * [
     *     JUNCTION_TABLE => [
     *         ALIAS_ID => ACTUAL_ID
     *     ]
     * ]
     */
    public function getJunctionAliases(): ?array;

    /**
     * Get IDs that should never be included as junctionIDs.
     *
     * @return array
     * [
     *     JUNCTION_TABLE => [JUNCTION_IDS]
     * ]
     */
    public function getJunctions(): ?array;
}

<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Interface PermissionJunctionModelInterface
 */
interface PermissionJunctionModelInterface {
    /**
     * Notify model  about permissions getting updated
     */
    public function clearPermissions(): void;
}

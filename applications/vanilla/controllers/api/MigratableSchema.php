<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;

/**
 * Add migratable fields to the API. These fields require the Site.Manage permission.
 */
class MigratableSchema extends Schema
{
    private array $migratableFields;

    /**
     * @param $schema
     */
    public function __construct($schema = [])
    {
        if (Gdn::session()->checkPermission(["Site.Manage"])) {
            $this->migratableFields = array_merge($schema, ["dateInserted:dt?", "dateUpdated:dt|n?"]);
        } else {
            $this->migratableFields = [];
        }

        parent::__construct($this->parseInternal($this->migratableFields));
    }
}

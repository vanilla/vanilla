<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 */

namespace Vanilla\Dashboard\Scope\Models;

use Gdn_Database;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Scope model for managing relationships between records and their scope.
 */
class ScopeModel extends PipelineModel
{
    // Record types that can be scoped
    const RECORD_TYPE_TAG = "tag";
    const RECORD_TYPE_STATUS = "status";

    // Scope record types
    const SCOPE_RECORD_TYPE_CATEGORY = "category";
    const SCOPE_RECORD_TYPE_SITE_SECTION = "siteSection";

    // Relation types
    const RELATION_TYPE_SCOPE = "scope";
    const RELATION_TYPE_DEFAULT = "default";

    // Scope types
    const SCOPE_TYPE_GLOBAL = "global";
    const SCOPE_TYPE_SCOPED = "scoped";

    /**
     * ScopeModel constructor.
     */
    public function __construct()
    {
        parent::__construct("scope");
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Override to normalize records from the database.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function select(array $where = [], array $options = []): array
    {
        $rows = parent::select($where, $options);
        return array_map(function ($row) {
            $row["scopeRecordID"] = match ($row["scopeRecordType"]) {
                "category" => (int) $row["scopeRecordID"],
                default => $row["scopeRecordID"],
            };
            return $row;
        }, $rows);
    }

    /**
     * Structure the Scope table.
     *
     * @param Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop
     * @return void
     */
    public static function structure(Gdn_Database $database, bool $explicit = false, bool $drop = false)
    {
        $construct = $database->structure();

        // Add scopeType column to Tag table for performance optimization
        $construct
            ->table("Tag")
            ->column("scopeType", [self::SCOPE_TYPE_GLOBAL, self::SCOPE_TYPE_SCOPED], self::SCOPE_TYPE_GLOBAL, "index")
            ->set($explicit, $drop);

        // Scope table - handles record-scope relationships
        $construct
            ->table("scope")
            ->primaryKey("scopeID")
            ->column("relationType", [self::RELATION_TYPE_SCOPE, self::RELATION_TYPE_DEFAULT])
            ->column("recordType", [self::RECORD_TYPE_TAG, self::RECORD_TYPE_STATUS])
            ->column("recordID", "int")
            ->column("scopeRecordType", [self::SCOPE_RECORD_TYPE_CATEGORY, self::SCOPE_RECORD_TYPE_SITE_SECTION])
            ->column("scopeRecordID", "varchar(64)") // Handles both int categoryID and string siteSectionID
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->column("insertUserID", "int")
            ->column("updateUserID", "int")
            ->set($explicit, $drop);

        // Create indexes for Scope table
        $construct
            ->table("scope")
            ->createIndexIfNotExists("UX_scope_relationType_recordType_recordID_scopeRecordType", [
                "relationType",
                "recordType",
                "recordID",
                "scopeRecordType",
            ]);
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Gdn_DatabaseStructure;

/**
 * Utilities that act on the `Gdn_SQLDriver` and `Gdn_Structure` classes.
 */
final class SqlUtils
{
    public const FEATURE_ALTER_TEXT_FIELD_LENGTHS = "alterTextFieldLengths";

    /**
     * Protect against alters of text field lengths.
     *
     * This method is meant to be run just before a call to `Gdn_DatabaseStructure::set()`. It ensures that existing
     * text fields don't get altered. Why? Well for two reasons:
     *
     * 1. Sometimes a site needs a different size text field (usually larger).
     * 2. It doesn't really hurt anything to have a different size text field on a site.
     * 3. Altering tables is expensive, especially on the types of tables that have text fields. Best to avoid that.
     *
     * You can call this method directly, but it's also something that can be bound to the `"gdn_mySQLStructure_beforeSet"`
     * event to act site-wide. Neat.
     *
     * @param Gdn_DatabaseStructure $structure
     */
    public static function keepTextFieldLengths(Gdn_DatabaseStructure $structure): void
    {
        $textTypes = ["tinytext" => 1, "text" => 2, "mediumtext" => 3, "longtext" => 4];
        $charTypes = ["varchar" => 1, "char" => 1, "varbinary" => 1, "binary" => 1];

        if (!$structure->tableExists()) {
            return;
        }

        $old = $structure->existingColumns();

        foreach ($old as $name => $oldDef) {
            $newDef = $structure->columns($name);
            if ($newDef === null) {
                continue;
            }
            $oldType = strtolower($oldDef->Type ?? "");
            $newType = strtolower($newDef->Type ?? "");

            if (isset($textTypes[$oldType])) {
                // The existing type is a text type. Never allow it to be changed here.
                $newDef->Type = $oldType;
                $newDef->Length = "";
            } elseif (isset($charTypes[$oldType]) && isset($charTypes[$newType])) {
                // The existing type is a varchar. Only allow it to grow, never shrink.
                $newDef->Length = max($oldDef->Length ?? 1, $newDef->Length ?? 1);
            }
        }
    }
}

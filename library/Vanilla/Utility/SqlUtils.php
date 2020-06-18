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
final class SqlUtils {
    public const FEATURE_ALTER_TEXT_FIELD_LENGTHS = 'alterTextFieldLengths';

    /**
     * @param Gdn_DatabaseStructure $structure
     */
    public static function keepTextFieldLengths(Gdn_DatabaseStructure $structure): void {
        $textTypes = ['tinytext' => 1, 'text' => 2, 'mediumtext' => 3, 'longtext' => 4];

        if (!$structure->tableExists()) {
            return;
        }

        $old = $structure->existingColumns();

        foreach ($old as $name => $oldDef) {
            $oldType = strtolower($oldDef->Type ?? '');
            if (!isset($textTypes[$oldType])) {
                continue;
            }

            if (null !== $newDef = $structure->columns($name)) {
                $newType = strtolower($newDef->Type ?? '');
                if ($textTypes[$oldType] !== ($textTypes[$newType] ?? 0)) {
                    $newDef->Type = $oldType;
                }
            }
        }
    }
}

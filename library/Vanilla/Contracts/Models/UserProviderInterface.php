<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Interface for modelling user expansion.
 */
interface UserProviderInterface extends FragmentProviderInterface {
    /**
     * Populate records with fragments based on various ID fields.
     *
     * @param array $records The records to populate.
     * @param array $columnNames The column names to check for. These should end with `ID`.
     *        The resulting values will be populated on a field without the ID suffix.
     */
    public function expandUsers(array &$records, array $columnNames): void;
}

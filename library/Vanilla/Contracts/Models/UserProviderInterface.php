<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Class for modelling user expansion.
 */
interface UserProviderInterface {

    /**
     * Get a single user fragment by it's ID.
     *
     * @param int $id The ID to lookup.
     * @param bool $useUnknownFallback Whether or not to use the unknown fragment as a fallback.
     * @return array
     */
    public function getFragmentByID(int $id, bool $useUnknownFallback = false): array;

    /**
     * Populate records with user fragments based on various ID fields.
     *
     * @param array $records The records to populate.
     * @param array $columnNames The column names to check for. These should end with `ID`.
     *        The resulting values will be populated on a field without the ID suffix.
     */
    public function expandUserFragments(array &$records, array $columnNames): void;

    /**
     * Get a fragment representing an unkown user.
     *
     * @return array
     */
    public function getUnknownFragment(): array;
}

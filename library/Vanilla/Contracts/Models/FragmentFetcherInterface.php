<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Interface for standard fetching of one or more resource record fragments.
 */
interface FragmentFetcherInterface {

    /**
     * Get resource fragments for one or more IDs.
     *
     * Fragments must include a "name" and "url" field. The resulting array should be indexed by record ID.
     *
     * @param int[] $ids The resource IDs to search for.
     * @param array $options Custom options for the operation.
     * @return array
     */
    public function fetchFragments(array $ids, array $options = []): array;
}

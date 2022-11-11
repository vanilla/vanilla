<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlopic.com>
 * @copyright 2009-2022 Higher Logic
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

/**
 * Interface for handling analytics Leaderboard queries.
 */
interface UserLeaderProviderInterface
{
    /**
     * Main method to generate leaderboard results.
     *
     * @param UserLeaderQuery $query
     * @return array
     */
    public function getLeaders(UserLeaderQuery $query): array;

    /**
     * Method to check if this method can handle this query.
     *
     * @param UserLeaderQuery $query
     * @return bool
     */
    public function canHandleQuery(UserLeaderQuery $query): bool;
}

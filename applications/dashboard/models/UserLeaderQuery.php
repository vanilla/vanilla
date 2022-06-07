<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlopic.com>
 * @copyright 2009-2022 Higher Logic
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Dashboard\UserLeaderService;

/**
 * Interface for containing Leaderboard query details.
 */
class UserLeaderQuery
{
    /** @var string */
    public $slotType;

    /** @var int|null  */
    public $categoryID;

    /** @var int|null  */
    public $limit;

    /** @var string  */
    public $leaderboardType;

    /** @var string  */
    public $timeSlot;

    /** @var int  */
    public $pointsCategoryID;

    /** @var array  */
    public $includedUserIDs;

    /** @var array  */
    public $excludedUserIDs;

    /**
     * Constructor of the User Leader.
     *
     * @param string $slotType
     * @param int|null $categoryID
     * @param int|null $limit
     * @param int[] $includedUserIDs
     * @param int[] $excludedUserIDs
     * @param string|null $leaderboardType
     */
    public function __construct(
        string $slotType,
        ?int $categoryID,
        ?int $limit,
        ?array $includedUserIDs = [],
        ?array $excludedUserIDs = [],
        ?string $leaderboardType = UserLeaderService::LEADERBOARD_TYPE_REPUTATION
    ) {
        $this->slotType = $slotType;
        $this->categoryID = $categoryID;
        $this->limit = $limit;
        $this->includedUserIDs = $includedUserIDs;
        $this->excludedUserIDs = $excludedUserIDs;
        $this->leaderboardType = $leaderboardType;
    }
}

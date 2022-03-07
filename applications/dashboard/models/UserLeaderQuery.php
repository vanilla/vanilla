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
class UserLeaderQuery {

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
    public $moderatorIDs;

    /**
     * Constructor of the User Leader.
     *
     * @param string $slotType Time interval type.
     * @param int|null $categoryID Category ID.
     * @param int|null $limit Number of results to return.
     * @param string|null $leaderboardType Leaderboard type.
     */
    public function __construct(string $slotType, ?int $categoryID, ?int $limit, ?string $leaderboardType = UserLeaderService::LEADERBOARD_TYPE_REPUTATION) {
        $this->slotType = $slotType;
        $this->categoryID = $categoryID;
        $this->limit = $limit;
        $this->leaderboardType = $leaderboardType;
    }
}

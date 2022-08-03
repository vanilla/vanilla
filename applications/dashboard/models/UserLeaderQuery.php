<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlopic.com>
 * @copyright 2009-2022 Higher Logic
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Utility\SerializedPublicPropertiesToJsonTrait;

/**
 * Interface for containing Leaderboard query details.
 */
class UserLeaderQuery implements \JsonSerializable
{
    use SerializedPublicPropertiesToJsonTrait;

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
    public $includedRoleIDs;

    /** @var array  */
    public $excludedRoleIDs;

    /**
     * Constructor of the User Leader.
     *
     * @param string $slotType
     * @param int|null $categoryID
     * @param int|null $limit
     * @param int[] $includedRoleIDs
     * @param int[] $excludedRoleIDs
     * @param string|null $leaderboardType
     */
    public function __construct(
        string $slotType,
        ?int $categoryID,
        ?int $limit,
        ?array $includedRoleIDs = [],
        ?array $excludedRoleIDs = [],
        ?string $leaderboardType = UserLeaderService::LEADERBOARD_TYPE_REPUTATION
    ) {
        $this->slotType = $slotType;
        $this->categoryID = $categoryID;
        $this->limit = $limit;
        $this->includedRoleIDs = $includedRoleIDs;
        $this->excludedRoleIDs = $excludedRoleIDs;
        $this->leaderboardType = $leaderboardType;
    }
}

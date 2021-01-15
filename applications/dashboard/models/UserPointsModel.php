<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;

/**
 * Model for UserPoints.
 */
class UserPointsModel extends Model {

    const SLOT_TYPE_DAY = "d";
    const SLOT_TYPE_WEEK = "w";
    const SLOT_TYPE_MONTH = "m";
    const SLOT_TYPE_YEAR = "y";
    const SLOT_TYPE_ALL = "a";
    const SLOT_TYPES = [
        self::SLOT_TYPE_DAY,
        self::SLOT_TYPE_WEEK,
        self::SLOT_TYPE_MONTH,
        self::SLOT_TYPE_YEAR,
        self::SLOT_TYPE_ALL,
    ];
    const USER_SLOT_TYPES = [
        // Day slot type is only used for total aggregate count on all days. UserID is 0.
        self::SLOT_TYPE_WEEK,
        self::SLOT_TYPE_MONTH,
        self::SLOT_TYPE_YEAR,
        self::SLOT_TYPE_ALL,
    ];
    const LIMIT_DEFAULT = 10;
    const DEFAULT_CACHE_TTL = 60 * 10; // 1 hour.
    const CONF_CACHE_TTL = 'Badges.LeaderBoardModule.CacheDefaultTTL';
    const CONF_EXCLUDE_PERMISSIONS = 'Badges.ExcludePermission';
    const ROOT_POINTS_CATEGORYID = 0;


    /** @var \CategoryModel */
    private $categoryModel;

    /** @var \UserModel */
    private $userModel;

    /** @var \RoleModel */
    private $roleModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var ModelCache */
    private $modelCache;

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     * @param \UserModel $userModel
     * @param \RoleModel $roleModel
     * @param ConfigurationInterface $config
     * @param \Gdn_Cache $cache
     */
    public function __construct(
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        \RoleModel $roleModel,
        ConfigurationInterface $config,
        \Gdn_Cache $cache
    ) {
        parent::__construct("UserPoints");
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->roleModel = $roleModel;
        $this->config = $config;
        $this->modelCache = new ModelCache('UserPoints', $cache);
    }


    /**
     * Get the points category from a categoryID.
     *
     * @param int|null $categoryID
     *
     * @return array|null
     */
    public function getPointsCategory(?int $categoryID = null): ?array {
        if ($categoryID !== null) {
            $category = $this->categoryModel::categories($categoryID);
            $categoryID = $category['PointsCategoryID'] ?? self::ROOT_POINTS_CATEGORYID;
            $category = $this->categoryModel::categories($categoryID);
            return $category;
        }
        return null;
    }

    /**
     * @return array
     */
    private function getModeratorIDs(): array {
        $excludePermission = $this->config->get(self::CONF_EXCLUDE_PERMISSIONS);
        if (!$excludePermission) {
            return [];
        }

        $rankedPermissions = [
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage'
        ];
        if (!in_array($excludePermission, $rankedPermissions)) {
            return [];
        }

        $moderatorIDs = [];
        $moderatorRoleIDs = [];
        $roles = $this->roleModel
            ->getWithRankPermissions()
            ->resultArray();

        $currentPermissionRank = array_search($excludePermission, $rankedPermissions);

        foreach ($roles as $currentRole) {
            for ($i = 0; $i <= $currentPermissionRank; $i++) {
                if (val($rankedPermissions[$i], $currentRole)) {
                    $moderatorRoleIDs[] = $currentRole['RoleID'];
                    continue 2;
                }
            }
        }

        if (!empty($moderatorRoleIDs)) {
            $moderators = $this->userModel->getByRole($moderatorRoleIDs)->resultArray();
            $moderatorIDs = array_column($moderators, 'UserID');
        }

        return $moderatorIDs;
    }

    /**
     * Get the leaders for a given slot type and time.
     *
     * @param string $slotType One of the SLOT_TYPE constants.
     * @param int|null $categoryID
     * @param int|null $limit
     *
     * @return array
     */
    public function getLeaders(string $slotType, ?int $categoryID = null, ?int $limit = null): array {
        if ($limit === null) {
            $limit = self::LIMIT_DEFAULT;
        }

        if ($categoryID === null) {
            $categoryID = 0;
        }

        $timeSlot = gmdate('Y-m-d', \Gdn_Statistics::timeSlotStamp($slotType, false));

        $pointsCategory = $this->getPointsCategory($categoryID);
        $pointsCategoryID = $pointsCategory['CategoryID'] ?? 0;
        $moderatorIDs = $this->getModeratorIDs();

        $args = [
            $slotType,
            $timeSlot,
            $pointsCategoryID,
            $moderatorIDs,
            $limit
        ];
        $leaderData = $this->modelCache->getCachedOrHydrate($args, [$this, 'queryLeaders'], [
            \Gdn_Cache::FEATURE_EXPIRY => $this->config->get(self::CONF_CACHE_TTL, self::DEFAULT_CACHE_TTL),
        ]);

        $this->userModel->joinUsers($leaderData, ['UserID'], ['Join' => ['Name', 'Email', 'Photo', 'Label']]);

        return $leaderData;
    }

    /**
     * Query the top userIDs in the leaderboard.
     *
     * @param string $slotType
     * @param string $timeSlot
     * @param int $categoryID
     * @param int[] $excludedUserIDs
     * @param int $limit
     *
     * @return int[]
     */
    public function queryLeaders(
        string $slotType,
        string $timeSlot,
        int $categoryID,
        array $excludedUserIDs,
        int $limit
    ) {
        $sql = $this->createSql();
        $sql->select([
                'SlotType',
                'TimeSlot',
                'Source',
                'CategoryID',
                'UserID',
                'Points'
            ])
            ->from('UserPoints')
            ->where([
                'TimeSlot' => $timeSlot,
                'SlotType' => $slotType,
                'Source' => 'Total',
                'CategoryID' => $categoryID,
            ])
            ->orderBy('Points', 'desc')
            ->limit($limit)
        ;

        if (!empty($excludedUserIDs)) {
            $sql->whereNotIn('UserID', $excludedUserIDs);
        }

        $results = $sql->get()->resultArray();
        return $results;
    }

    /**
     * Get a title for a given slot type.
     *
     * @param string $slotType
     * @param string $in
     *
     * @return string
     */
    public function getTitleForSlotType(string $slotType, string $in = '') {
        switch ($slotType) {
            case UserPointsModel::SLOT_TYPE_WEEK:
                $str = "This Week's Leaders";
                break;
            case UserPointsModel::SLOT_TYPE_MONTH:
                $str = "This Month's Leaders";
                break;
            case UserPointsModel::SLOT_TYPE_ALL:
                $str = "All Time Leaders";
                break;
            default:
                $str = "Leaders";
                break;
        }

        if ($in) {
            return sprintf(t($str.' in %s'), htmlspecialchars($in));
        } else {
            return t($str);
        }
    }
}

<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Higher Logic.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Garden\Web\Exception\NotFoundException;
use RoleModel;
use Vanilla\Dashboard\Models\UserLeaderProviderInterface;
use UserModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Site\SiteSectionModel;
use function PHPUnit\Framework\isEmpty;

/**
 * Service to load leaderboards.
 */
class UserLeaderService
{
    const LEADERBOARD_TYPE_REPUTATION = "reputation";
    const LEADERBOARD_TYPE_POSTS = "posts";
    const LEADERBOARD_TYPE_ACCEPTED_ANSWERS = "acceptedAnswers";
    const LEADERBOARD_TYPES = [
        self::LEADERBOARD_TYPE_REPUTATION,
        self::LEADERBOARD_TYPE_POSTS,
        self::LEADERBOARD_TYPE_ACCEPTED_ANSWERS,
    ];

    const LIMIT_DEFAULT = 10;
    const DEFAULT_CACHE_TTL = 60 * 10; // 1 hour.
    const CONF_CACHE_TTL = "Badges.LeaderBoardModule.CacheDefaultTTL";
    const CONF_EXCLUDE_PERMISSIONS = "Badges.ExcludePermission";
    const ROOT_POINTS_CATEGORYID = 0;

    /** @var UserLeaderProviderInterface[] */
    private $providers;

    /** @var \UserModel */
    private $userModel;

    /** @var \RoleModel */
    private $roleModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param UserModel $userModel
     * @param RoleModel $roleModel
     * @param ConfigurationInterface $config
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        \UserModel $userModel,
        \RoleModel $roleModel,
        ConfigurationInterface $config,
        SiteSectionModel $siteSectionModel
    ) {
        $this->userModel = $userModel;
        $this->roleModel = $roleModel;
        $this->config = $config;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Add UserLeaderProviderInterface provider
     *
     * @param UserLeaderProviderInterface $provider
     */
    public function addProvider(UserLeaderProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Iterate over the providers to generate Leaderboards.
     *
     * @param UserLeaderQuery $query Query parameters.
     * @return array
     * @throws \Exception Time slot exception.
     */
    public function getLeaders(UserLeaderQuery $query): array
    {
        if ($query->limit === null) {
            $query->limit = self::LIMIT_DEFAULT;
        }

        if ($query->categoryID === null) {
            $query->categoryID = 0;
        }

        $query->timeSlot = gmdate("Y-m-d", \Gdn_Statistics::timeSlotStamp($query->slotType, false));

        if (!empty($query->siteSectionID)) {
            $siteSection = $this->siteSectionModel->getByID($query->siteSectionID);
            $query->pointsCategoryID = $siteSection ? $siteSection->getAttributes()["allCategories"] : 0;
        } else {
            $query->pointsCategoryID = $query->categoryID ?? 0;
        }
        // We add moderators' user IDs to the excluded user IDs.
        if (empty($query->excludedRoleIDs)) {
            $moderatorRoleIDs = $this->getModeratorRoleIDs();
            if (!empty($query->includedRoleIDs)) {
                $moderatorRoleIDs = array_filter($moderatorRoleIDs, function ($id) use ($query) {
                    // Don't automatically add moderator's role ids if they weren't explicitly included.
                    return !in_array($id, $query->includedRoleIDs);
                });
            }
            $query->excludedRoleIDs = $moderatorRoleIDs;
        }

        foreach ($this->providers as $provider) {
            if ($provider->canHandleQuery($query)) {
                $leaderData = $provider->getLeaders($query);
                $this->userModel->joinUsers($leaderData, ["UserID"]);
                return $leaderData;
            }
        }

        // Throw exception when no provider is found.
        throw new NotFoundException($query->leaderboardType . " provider could not be found");
    }

    /**
     * @return UserLeaderProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param UserLeaderProviderInterface[] $providers
     */
    public function setProviders(array $providers): void
    {
        $this->providers = $providers;
    }

    /**
     * Get a title for a given slot type.
     *
     * @param string $slotType
     * @param string $in
     *
     * @return string
     */
    public function getTitleForSlotType(string $slotType, string $in = "")
    {
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
            return sprintf(t($str . " in %s"), htmlspecialchars($in));
        } else {
            return t($str);
        }
    }

    /**
     * Get Moderator IDs to exclude from results.
     *
     * @return array
     */
    private function getModeratorRoleIDs(): array
    {
        $excludePermission = $this->config->get(self::CONF_EXCLUDE_PERMISSIONS);
        if (!$excludePermission) {
            return [];
        }

        $rankedPermissions = ["Garden.Settings.Manage", "Garden.Community.Manage", "Garden.Moderation.Manage"];
        if (!in_array($excludePermission, $rankedPermissions)) {
            return [];
        }

        $moderatorIDs = [];
        $moderatorRoleIDs = [];
        $roles = $this->roleModel->getWithRankPermissions()->resultArray();

        $currentPermissionRank = array_search($excludePermission, $rankedPermissions);

        foreach ($roles as $currentRole) {
            for ($i = 0; $i <= $currentPermissionRank; $i++) {
                if (val($rankedPermissions[$i], $currentRole)) {
                    $moderatorRoleIDs[] = $currentRole["RoleID"];
                    continue 2;
                }
            }
        }

        return $moderatorRoleIDs;
    }
}

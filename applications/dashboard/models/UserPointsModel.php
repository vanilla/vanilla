<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Garden\Schema\Schema;
use Vanilla\Addon;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\UserLeaderProviderInterface;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;

/**
 * Model for UserPoints.
 */
class UserPointsModel extends Model implements UserLeaderProviderInterface
{
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

    /** @var ConfigurationInterface */
    private $config;

    /** @var ModelCache */
    private $modelCache;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Cache $cache
     */
    public function __construct(ConfigurationInterface $config, \Gdn_Cache $cache)
    {
        parent::__construct("UserPoints");
        $this->config = $config;
        $this->modelCache = new ModelCache("UserPoints", $cache);
    }

    /**
     * Get the leaders by the queried criteria.
     *
     * @param UserLeaderQuery $query
     *
     * @return array
     */
    public function getLeaders(UserLeaderQuery $query): array
    {
        $leaderData = $this->modelCache->getCachedOrHydrate(
            [$query],
            [$this, "queryLeaders"],
            [
                \Gdn_Cache::FEATURE_EXPIRY => $this->config->get(
                    UserLeaderService::CONF_CACHE_TTL,
                    UserLeaderService::DEFAULT_CACHE_TTL
                ),
            ]
        );

        return $leaderData;
    }

    /**
     * Query the top userIDs in the leaderboard.
     *
     * @param UserLeaderQuery $query
     *
     * @return int[]
     */
    public function queryLeaders(UserLeaderQuery $query)
    {
        $roleSubQuery = $this->getAllowedRolesSubquery($query);

        $leaderQuery = $this->createSql()
            ->select(["up.SlotType", "up.TimeSlot", "up.Source", "up.UserID"])
            ->from("UserPoints up")
            ->join("User u", "up.UserID = u.UserID and u.Banned != 1")
            ->where([
                "up.SlotType" => $query->slotType,
                "up.TimeSlot" => $query->timeSlot,
                "up.Source" => "Total",
                "up.CategoryID" => $query->pointsCategoryID,
                "up.Points > " => 0,
            ]);
        // Only group by if we have more than one category.
        if (is_array($query->pointsCategoryID) && count($query->pointsCategoryID) > 1) {
            $leaderQuery
                ->select("SUM(up.Points) as Points")
                ->groupBy(["up.SlotType", "up.TimeSlot", "up.Source", "up.UserID"]);
        } else {
            $leaderQuery->select("up.Points");
        }

        if ($roleSubQuery !== null) {
            $leaderQuery = $leaderQuery->where("`up`.`UserID` in", "({$roleSubQuery->getSelect(true)})", false, false);
        }

        $leaderQuery = $leaderQuery->orderBy("Points", "desc")->limit($query->limit);

        $results = $leaderQuery->get()->resultArray();
        return $results;
    }

    /**
     * Get the allowed roleIDs for our query.
     *
     * @return \Gdn_SQLDriver|null Returns null if we have no filter.
     */
    private function getAllowedRolesSubquery(UserLeaderQuery $leaderQuery): ?\Gdn_SQLDriver
    {
        if (empty($leaderQuery->includedRoleIDs) && empty($leaderQuery->excludedRoleIDs)) {
            return null;
        }

        $subQuery = $this->createSql()
            ->select("UserID")
            ->from("UserRole");
        if (!empty($leaderQuery->excludedRoleIDs)) {
            $notSubQuery = $this->createSql()
                ->select("UserID")
                ->from("UserRole")
                ->where("RoleID", $leaderQuery->excludedRoleIDs);
            $subQuery = $subQuery->where("`UserID` not in", "({$notSubQuery->getSelect(true)})", false, false);
        }

        if (!empty($leaderQuery->includedRoleIDs)) {
            $subQuery->where("RoleID", $leaderQuery->includedRoleIDs);
        }
        return $subQuery;
    }

    /**
     * The slot type schema for calculating leaders
     *
     * @return Schema
     */
    public static function slotTypeSchema(): Schema
    {
        return Schema::parse([
            "type" => "string",
            "default" => UserPointsModel::SLOT_TYPE_ALL,
            "description" => "The timeframe in which leaders should calculated",
            "enum" => UserPointsModel::SLOT_TYPES,
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Timeframe", "Choose what duration to check for leaders in."),
                self::getSlotTypeChoices()
            ),
        ]);
    }

    /**
     * The site section schema for calculating leaders
     *
     * @return Schema
     */
    public static function siteSectionSchema(): Schema
    {
        return Schema::parse([
            "type" => "string",
            "default" => "",
            "description" => "Filter User Points by site section ID (ex. subcommunity).
                      The subcommunity ID or folder can be used if you use [smart IDs](https://success.vanillaforums.com/kb/articles/46-smart-ids).
                      The query looks like:

                      ```
                      siteSectionID=\$subcommunityID:{id|folder}
                      ```",
            "x-control" => SchemaForm::textBox(
                new FormOptions("SiteSectionID", "Filter User Points by site section ID ."),
                "string"
            ),
        ]);
    }

    /**
     * Get a StaticFormChoices object.
     *
     * @return StaticFormChoices
     */
    public static function getSlotTypeChoices(): StaticFormChoices
    {
        return new StaticFormChoices([
            UserPointsModel::SLOT_TYPE_DAY => "Daily",
            UserPointsModel::SLOT_TYPE_WEEK => "Weekly",
            UserPointsModel::SLOT_TYPE_MONTH => "Monthly",
            UserPointsModel::SLOT_TYPE_YEAR => "Yearly",
            UserPointsModel::SLOT_TYPE_ALL => "All Time",
        ]);
    }

    /**
     * The leaderboard type schema for calculating leaders
     *
     * @return Schema
     */
    public static function leaderboardTypeSchema(): Schema
    {
        $formChoices = [
            UserLeaderService::LEADERBOARD_TYPE_REPUTATION => "Reputation points",
        ];
        if (\Gdn::addonManager()->isEnabled("vanillaanalytics", Addon::TYPE_ADDON)) {
            $formChoices = array_merge($formChoices, [
                UserLeaderService::LEADERBOARD_TYPE_ACCEPTED_ANSWERS => "Accepted answers count",
            ]);
        }
        if (\Gdn::addonManager()->isEnabled("ElasticSearch", Addon::TYPE_ADDON)) {
            $formChoices = array_merge($formChoices, [
                UserLeaderService::LEADERBOARD_TYPE_POSTS => "Posts and comments count",
            ]);
        }
        return Schema::parse([
            "type" => "string",
            "default" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
            "description" => "The type of points to use for leaderboard.",
            "enum" => UserLeaderService::LEADERBOARD_TYPES,
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Leaderboard Type", "Choose the type of leaderboard this is."),
                new StaticFormChoices($formChoices)
            ),
        ]);
    }

    /**
     * The user limit schema
     *
     * @return Schema
     */
    public static function limitSchema(): Schema
    {
        return Schema::parse([
            "type" => "integer",
            "default" => 10,
            "description" => "The maximum number of users to display",
            "x-control" => SchemaForm::textBox(
                new FormOptions("Limit", "Maximum amount of users to display."),
                "number"
            ),
        ]);
    }

    /**
     * Method to check if this class can handle this query.
     *
     * @param UserLeaderQuery $query
     * @return bool
     */
    public function canHandleQuery(UserLeaderQuery $query): bool
    {
        return $query->leaderboardType === UserLeaderService::LEADERBOARD_TYPE_REPUTATION;
    }
}

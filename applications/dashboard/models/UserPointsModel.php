<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Garden\Schema\Schema;
use UserLeaderProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;

/**
 * Model for UserPoints.
 */
class UserPointsModel extends Model implements UserLeaderProviderInterface {

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
    public function __construct(
        ConfigurationInterface $config,
        \Gdn_Cache $cache
    ) {
        parent::__construct("UserPoints");
        $this->config = $config;
        $this->modelCache = new ModelCache('UserPoints', $cache);
    }

    /**
     * Get the leaders for a given slot type and time.
     *
     * @param UserLeaderQuery $query
     *
     * @return array
     */
    public function getLeaders(UserLeaderQuery $query): array {
        $args = [
            $query->slotType,
            $query->timeSlot,
            $query->pointsCategoryID,
            $query->moderatorIDs,
            $query->limit
        ];
        $leaderData = $this->modelCache->getCachedOrHydrate($args, [$this, 'queryLeaders'], [
            \Gdn_Cache::FEATURE_EXPIRY => $this->config->get(UserLeaderService::CONF_CACHE_TTL, UserLeaderService::DEFAULT_CACHE_TTL),
        ]);

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
            'up.SlotType',
            'up.TimeSlot',
            'up.Source',
            'up.CategoryID',
            'up.UserID',
            'up.Points'
        ])
            ->from('UserPoints up')
            ->join('User u', 'up.UserID = u.UserID and u.Banned != 1')
            ->where([
                'up.TimeSlot' => $timeSlot,
                'up.SlotType' => $slotType,
                'up.Source' => 'Total',
                'up.CategoryID' => $categoryID,
                'up.Points > ' => 0,

            ])
            ->orderBy('up.Points', 'desc')
            ->limit($limit);

        if (!empty($excludedUserIDs)) {
            $sql->whereNotIn('up.UserID', $excludedUserIDs);
        }

        $results = $sql->get()->resultArray();
        return $results;
    }

    /**
     * The slot type schema for calculating leaders
     *
     * @return Schema
     */
    public static function slotTypeSchema(): Schema {
        return Schema::parse([
            "type" => "string",
            "default" => UserPointsModel::SLOT_TYPE_ALL,
            "description" => "The timeframe in which leaders should calculated",
            "enum" => UserPointsModel::SLOT_TYPES,
            "x-control" => SchemaForm::dropDown(
                new FormOptions(
                    "Timeframe",
                    "Choose what duration to check for leaders in."
                ),
                self::getSlotTypeChoices()
            )
        ]);
    }

    /**
     * Get a StaticFormChoices object.
     *
     * @return StaticFormChoices
     */
    public static function getSlotTypeChoices(): StaticFormChoices {
        return new StaticFormChoices(
            [
                UserPointsModel::SLOT_TYPE_DAY => "Daily",
                UserPointsModel::SLOT_TYPE_WEEK => "Weekly",
                UserPointsModel::SLOT_TYPE_MONTH => "Monthly",
                UserPointsModel::SLOT_TYPE_YEAR => "Yearly",
                UserPointsModel::SLOT_TYPE_ALL => "All Time",
            ]
        );
    }

    /**
     * The leaderboard type schema for calculating leaders
     *
     * @return Schema
     */
    public static function leaderboardTypeSchema(): Schema {
        return Schema::parse([
            "type" => "string",
            "default" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
            "description" => "The type of points to use for leaderboard.",
            "enum" => UserLeaderService::LEADERBOARD_TYPES,
            "x-control" => SchemaForm::dropDown(
                new FormOptions(
                    "Leaderboard Type",
                    "Choose the type of leaderboard this is."
                ),
                new StaticFormChoices(
                    [
                        UserLeaderService::LEADERBOARD_TYPE_REPUTATION => "Reputation points",
                        UserLeaderService::LEADERBOARD_TYPE_POSTS => "Posts and comments count.",
                        UserLeaderService::LEADERBOARD_TYPE_ACCEPTED_ANSWERS => "Accepted answers count.",
                    ]
                )
            )
        ]);
    }

    /**
     * The user limit schema
     *
     * @return Schema
     */
    public static function limitSchema(): Schema {
        return Schema::parse([
            "type" => "integer",
            "default" => 10,
            "description" => "The maximum number of users to display",
            "x-control" => SchemaForm::textBox(
                new FormOptions(
                    "Limit",
                    "Maximum amount of users to display."
                ),
                "number"
            )
        ]);
    }

    /**
     * Method to check if this class can handle this query.
     *
     * @param UserLeaderQuery $query
     * @return bool
     */
    public function canHandleQuery(UserLeaderQuery $query): bool {
        return $query->leaderboardType === UserLeaderService::LEADERBOARD_TYPE_REPUTATION;
    }
}

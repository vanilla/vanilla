<?php
/**
 * Ban Model.
 *
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerMultiAction;

/**
 * Manage banning of users.
 *
 * @since 2.0.18
 * @package Dashboard
 */
class BanModel extends Gdn_Model
{
    const ACTION_BAN = "ban";
    const ACTION_UNBAN = "unban";

    const CACHE_KEY = "allBans";
    const CACHE_TTL = 60 * 30; // 30 minutes.

    /** Manually banned by a moderator. */
    const BAN_MANUAL = 0x1;

    /** Automatically banned by an IP ban, name, or email ban. */
    const BAN_AUTOMATIC = 0x2;

    /** Reserved for future functionality. */
    const BAN_TEMPORARY = 0x4;

    /** Banned by the warnings plugin. */
    const BAN_WARNING = 0x8;

    /* @var array */
    protected static $_AllBans;

    /** @var BanModel The singleton instance of this class. */
    protected static $instance;

    /** @var LongRunner */
    private $longRunner;

    /**
     * Defines the related database table name.
     */
    public function __construct()
    {
        parent::__construct("Ban");
        $this->fireEvent("Init");
        $this->longRunner = Gdn::getContainer()->get(LongRunner::class);
    }

    /**
     * Get the singleton instance of the {@link BanModel} class.
     *
     * @return BanModel Returns the singleton instance of this class.
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BanModel();
        }
        return self::$instance;
    }

    /**
     * Get and store list of current bans.
     *
     * @since 2.0.18
     * @access public
     */
    public static function &allBans()
    {
        if (!self::$_AllBans) {
            $cache = \Gdn::cache();
            $bans = $cache->get(self::CACHE_KEY);
            if ($bans === \Gdn_Cache::CACHEOP_FAILURE) {
                $bans = Gdn::sql()
                    ->get("Ban")
                    ->resultArray();
                $bans = array_column($bans, null, "BanID");
                $cache->store(self::CACHE_KEY, $bans, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_KEY]);
            }
            self::$_AllBans = $bans;
        }
        return self::$_AllBans;
    }

    /**
     * Clean the ban cache.
     */
    public static function clearCache()
    {
        \Gdn::cache()->remove(self::CACHE_KEY);
    }

    /**
     * Clear the cache on updates.
     */
    protected function onUpdate()
    {
        self::clearCache();
    }

    /**
     * Convert bans to new type.
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $newBan Data about the new ban.
     * @param array $oldBan Data about the old ban.
     */
    public function applyBan($newBan = null, $oldBan = null)
    {
        if (!$newBan && !$oldBan) {
            return;
        }
        if ($oldBan != null) {
            unset($oldBan["InsertIPAddress"]);
            unset($oldBan["UpdateIPAddress"]);
            $processAction[] = new \Vanilla\Scheduler\LongRunnerAction(
                BanUserCountGenerator::class,
                "processUserUnBans",
                [null, null, $newBan, $oldBan]
            );
        }
        if ($newBan != null) {
            $processAction[] = new \Vanilla\Scheduler\LongRunnerAction(
                BanUserCountGenerator::class,
                "processUserBans",
                [null, null, $newBan]
            );
        }

        $this->longRunner->runDeferred(new LongRunnerMultiAction($processAction));
    }

    /**
     * Ban users that meet conditions given.
     *
     * @since 2.0.18
     * @access public
     * @param array $ban Data about the ban.
     *    Valid keys are BanType and BanValue. BanValue is what is to be banned.
     *    Valid values for BanType are email, ipaddress or name.
     *
     * @param string $prepend
     * @return array
     */
    public function banWhere($ban, string $prepend = "", bool $inverse = false)
    {
        $result = ["{$prepend}Admin" => 0, "{$prepend}Deleted" => 0];
        $ban["BanValue"] = str_replace("*", "%", $ban["BanValue"]);
        $inverse = $inverse ? " NOT " : "";
        switch (strtolower($ban["BanType"])) {
            case "email":
                $result["{$prepend}Email {$inverse} like"] = $ban["BanValue"];
                break;
            case "ipaddress":
                $result["inet6_ntoa({$prepend}LastIPAddress) {$inverse} like"] = $ban["BanValue"];
                break;
            case "name":
                $result["{$prepend}Name {$inverse} like"] = $ban["BanValue"];
                break;
            default:
                $result = $this->getEventManager()->fireFilter("banModel_banWhere", $result, $ban, $prepend, $inverse);
                break;
        }
        return $result;
    }

    /**
     * Add ban data to all Get requests.
     *
     * @since 2.0.18
     * @access public
     */
    public function _BeforeGet()
    {
        $this->SQL
            ->select("Ban.*")
            ->select("iu.Name", "", "InsertName")
            ->join("User iu", "Ban.InsertUserID = iu.UserID", "left");

        parent::_BeforeGet();
    }

    /**
     * Add ban data to all Get requests.
     *
     * @since 2.0.18
     * @access public
     *
     * @param mixed User data (array or object).
     * @param Gdn_Validation $Validation
     * @param bool $UpdateBlocks
     * @return bool Whether user is banned.
     */
    public static function checkUser($User, $Validation = null, $UpdateBlocks = false, &$BansFound = null)
    {
        $Bans = self::allBans();

        $Banned = [];

        if (!$BansFound) {
            $BansFound = [];
        }

        foreach ($Bans as $Ban) {
            // Convert ban to regex.
            $Parts = explode("*", str_replace("%", "*", $Ban["BanValue"]));
            $Parts = array_map("preg_quote", $Parts);
            $Regex = "`^" . implode(".*", $Parts) . '$`i';

            if ($Ban["BanType"] === "IPAddress") {
                $value = ipDecode(val("LastIPAddress", $User));
            } else {
                $value = val($Ban["BanType"], $User);
            }

            if (preg_match($Regex, $value)) {
                $Banned[$Ban["BanType"]] = true;
                $BansFound[] = $Ban;

                if ($UpdateBlocks) {
                    Gdn::sql()
                        ->update("Ban")
                        ->set("CountBlockedRegistrations", "CountBlockedRegistrations + 1", false, false)
                        ->where("BanID", $Ban["BanID"])
                        ->put();
                    self::clearCache();
                }
            }
        }

        // Add the validation results.
        if ($Validation) {
            foreach ($Banned as $BanType => $Value) {
                $Validation->addValidationResult(Gdn_Form::labelCode($BanType), "ValidateBanned");
            }
        }
        return count($Banned) == 0;
    }

    /**
     * Remove a ban.
     *
     * @param array|int $where The where clause to delete or an integer value.
     * @param array|true $options An array of options to control the delete.
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function delete($where = [], $options = [])
    {
        if (isset($where["BanID"])) {
            $oldBan = $this->getID($where["BanID"], DATASET_TYPE_ARRAY);
        }

        $result = parent::delete($where, $options);

        if (isset($oldBan)) {
            $this->applyBan(null, $oldBan);
        }

        return $result;
    }

    /**
     * Explode a banned bit mask into an array of ban constants.
     * @param int $banned The banned bit mask to explode.
     * @return array Returns an array of the set bits.
     */
    public static function explodeBans($banned)
    {
        $result = [];

        for ($i = 1; $i <= 8; $i++) {
            $bit = pow(2, $i - 1);
            if (($banned & $bit) === $bit) {
                $result[] = $bit;
            }
        }

        return $result;
    }

    /**
     * Check whether or not a banned value is banned for a given reason.
     *
     * @param int $banned The banned value.
     * @param int $reason The reason for the banning or an empty string to check if banned for any reason.
     * This should be one of the `BanModel::BAN_*` constants.
     * @return bool Returns true if the value is banned or false otherwise.
     */
    public static function isBanned($banned, $reason = 0)
    {
        if (!$reason) {
            return (bool) $banned;
        } else {
            return ($banned & $reason) > 0;
        }
    }

    /**
     * Set the banned mask value for a reason and return the new value.
     *
     * @param int $banned The current banned value.
     * @param bool $value The new ban value for the given reason.
     * @param int $reason The reason for the banning. This should be one of the `BanModel::BAN_*` constants.
     * @return int Returns the new banned value.
     */
    public static function setBanned($banned, $value, $reason)
    {
        if ($value) {
            $banned = $banned | $reason;
        } else {
            $banned = $banned & ~$reason;
        }
        return $banned;
    }

    /**
     * Save data about ban from form.
     *
     * @param array $formPostValues
     * @param array|false $settings
     */
    public function save($formPostValues, $settings = false)
    {
        $currentBanID = val("BanID", $formPostValues);

        // Get the current ban before saving.
        if ($currentBanID) {
            $currentBan = $this->getID($currentBanID, DATASET_TYPE_ARRAY);
        } else {
            $currentBan = null;
        }

        $banID = parent::save($formPostValues, $settings);
        $formPostValues["BanID"] = $banID;

        $this->EventArguments["CurrentBan"] = $currentBan;
        $this->EventArguments["FormPostValues"] = $formPostValues;
        $this->fireEvent("AfterSave");

        $this->applyBan($formPostValues, $currentBan);
        return $formPostValues;
    }

    /**
     * Change ban data on a user (ban or unban them).
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $user
     * @param bool $bannedValue Whether user is banned.
     * @param array|false $ban An array representing the specific auto-ban.
     */
    public function saveUser($user, $bannedValue, $ban = false)
    {
        $bannedValue = (bool) $bannedValue;
        $banned = $user["Banned"];

        if (static::isBanned($banned, self::BAN_AUTOMATIC) === $bannedValue) {
            return;
        }

        $newBanned = static::setBanned($banned, $bannedValue, self::BAN_AUTOMATIC);
        Gdn::userModel()->save(["UserID" => $user["UserID"], "Banned" => $newBanned]);
        $banningUserID = Gdn::session()->UserID;
        // This is true when a session is started and the session user has a new ip address and it matches a banning rule ip address
        if ($user["UserID"] == $banningUserID) {
            $banningUserID = val("InsertUserID", $ban, Gdn::userModel()->getSystemUserID());
        }

        // Create and dispatch a UserDisciplineEvent
        $action = $bannedValue ? self::ACTION_BAN : self::ACTION_UNBAN;
        $disciplineType = $bannedValue
            ? \Vanilla\Dashboard\Events\UserDisciplineEvent::DISCIPLINE_TYPE_NEGATIVE
            : \Vanilla\Dashboard\Events\UserDisciplineEvent::DISCIPLINE_TYPE_POSITIVE;
        $source = $ban ? "ban_rules" : null;
        $banEvent = Gdn::userModel()->createUserDisciplineEvent(
            $user["UserID"],
            $action,
            $disciplineType,
            $source,
            $banningUserID
        );
        if ($ban) {
            $banEvent->setReason("{$ban["BanType"]} matches {$ban["BanValue"]}");
        }
        $eventManager = $this->getEventManager();
        $eventManager->dispatch($banEvent);

        // Log the ban.
        $bannedString = $bannedValue ? "banned" : "unbanned";
        if (is_array($ban)) {
            Logger::event(
                \Vanilla\Events\EventAction::eventName("user", \Vanilla\Events\EventAction::BAN),
                \Psr\Log\LogLevel::INFO,
                "{" . Logger::FIELD_TARGET_USERNAME . "} was auto-$bannedString by {banType}.",
                [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_MODERATION,
                    Logger::FIELD_TARGET_USERID => $user["UserID"],
                    Logger::FIELD_USERID => $banningUserID,
                    "banned" => $bannedValue,
                    "banType" => strtolower($ban["BanType"]),
                    "banValue" => $ban["BanValue"],
                ]
            );
        } else {
            Logger::event(
                \Vanilla\Events\EventAction::eventName("user", \Vanilla\Events\EventAction::BAN),
                \Psr\Log\LogLevel::INFO,
                "{" . Logger::FIELD_TARGET_USERNAME . "} was auto-$bannedString.",
                [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_MODERATION,
                    Logger::FIELD_TARGET_USERID => $user["UserID"],
                    Logger::FIELD_USERID => $banningUserID,
                    "banned" => $bannedValue,
                ]
            );
        }

        // Add the activity.
        $activityModel = new ActivityModel();
        $activity = [
            "ActivityType" => "Ban",
            "ActivityUserID" => $user["UserID"],
            "RegardingUserID" => $banningUserID,
            "NotifyUserID" => ActivityModel::NOTIFY_MODS,
        ];
        if ($ban) {
            if ($banned != $newBanned) {
                // If its currently existing rule, and there was a change in Banned status update UserCount.
                $this->incrementCount($ban["BanID"], $bannedValue ? 1 : -1);
            }
            $activity["HeadlineFormat"] =
                "{ActivityUserID,user} was " . $bannedString . " (based on {Data.BanType}: {Data.BanValue}).";
            $activity["Data"] = arrayTranslate($ban, ["BanType", "BanValue"]);
            $activity["Story"] = $ban["Notes"];
            $activity["RecordType"] = "Ban";

            if (isset($ban["BanID"])) {
                $activity["BanID"] = $ban["BanID"];
            }
        } else {
            $activity["HeadlineFormat"] = "{ActivityUserID,user} was " . $bannedString . ".";
        }
        $activityModel->save($activity);
    }

    /**
     * Increment/decrement CountUsers for the ban rule applied.
     *
     * @param int $banID
     * @param int $amount value to increment/decrement ban rule CountUsers field by.
     * @throws Exception
     */
    public function incrementCount(int $banID, int $amount = 1): void
    {
        $this->SQL
            ->update("Ban")
            ->set("CountUsers", "CountUsers + {$amount}", false)
            ->where("BanID", $banID)
            ->put();
    }
}

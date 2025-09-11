<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Class used to send notification for a specific set of permission and preferences.
 */
class PermissionNotificationGenerator implements SystemCallableInterface
{
    const BATCH_SIZE_CONFIG_KEY = "Vanilla.Permission.NotificationBatchSize";
    private int $batchSize;

    /**
     * D.I.
     *
     * @param PermissionModel $permissionModel
     * @param Gdn_Database $database
     * @param UserModel $usermodel
     * @param UserMetaModel $userMetaModel
     * @param ActivityModel $activityModel
     * @param LongRunner $longRunner
     * @param ConfigurationInterface $config
     */
    public function __construct(
        protected PermissionModel $permissionModel,
        protected Gdn_Database $database,
        protected UserModel $usermodel,
        protected UserMetaModel $userMetaModel,
        protected ActivityModel $activityModel,
        protected LongRunner $longRunner,
        protected ConfigurationInterface $config
    ) {
        $this->batchSize = $this->config->get(self::BATCH_SIZE_CONFIG_KEY, 100);
    }

    /**
     * @return array|string[]
     */
    public static function getSystemCallableMethods(): array
    {
        return ["notificationGenerator", "getUsersToNotifyIteratorCount"];
    }

    /**
     * Send out the notification.
     *
     * @param array $activity
     * @param array|string $permissions
     * @param string $preference
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @param bool $hasDefaultPreferences
     * @return void
     */
    public function notify(
        array $activity,
        array|string $permissions,
        string $preference,
        ?string $junctionTable = null,
        ?int $junctionID = null,
        bool $hasDefaultPreferences = false,
        array $options = [],
        ?int $discussionID = null
    ): void {
        $action = new LongRunnerAction(self::class, "notificationGenerator", [
            $activity,
            $permissions,
            $preference,
            0,
            $junctionTable,
            $junctionID,
            $hasDefaultPreferences,
            $options,
            $discussionID,
        ]);

        $this->longRunner->runDeferred($action);
    }

    /**
     * Long runner sending out notifications based on specific set of preferences and permissions.
     *
     * @param array $activity
     * @param array|string $permissions
     * @param string $preference
     * @param int $lastUserID
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @param bool $supportDefaultPreferences
     * @param array $options
     * @return Generator
     * @throws Exception
     */
    public function notificationGenerator(
        array $activity,
        array|string $permissions,
        string $preference,
        int $lastUserID = 0,
        ?string $junctionTable = null,
        ?int $junctionID = null,
        bool $supportDefaultPreferences = false,
        array $options = [],
        ?int $discussionID = null
    ): Generator {
        $activityType = $activity["ActivityType"] ?? "Default";
        $defaultEmail = $supportDefaultPreferences && $this->config->get("Preferences.Email.$preference");
        $defaultNotified = $supportDefaultPreferences && $this->config->get("Preferences.Popup.$preference");
        $hasDefaultPreferences = $defaultEmail || $defaultNotified;

        // Yield total users to notify.
        yield new LongRunnerQuantityTotal(
            [$this, "getUsersToNotifyIteratorCount"],
            [$permissions, $preference, $junctionTable, $junctionID, $hasDefaultPreferences, $discussionID]
        );

        // Start sending the notifications.
        do {
            $usersToNotify = $this->getUsersWithPreferences(
                $permissions,
                $preference,
                $lastUserID,
                $junctionTable,
                $junctionID,
                $hasDefaultPreferences,
                $discussionID
            );

            foreach ($usersToNotify as $userID => $userPreferences) {
                try {
                    if (!$this->usermodel->checkPermission($userID, $permissions)) {
                        continue;
                    }

                    $activity["NotifyUserID"] = $userID;
                    $activity["Emailed"] =
                        $userPreferences["Emailed"] ?? false
                            ? ActivityModel::SENT_PENDING
                            : ActivityModel::SENT_SKIPPED;
                    $activity["Notified"] =
                        $userPreferences["Notified"] ?? false
                            ? ActivityModel::SENT_PENDING
                            : ActivityModel::SENT_SKIPPED;

                    $longRunnerID = "User_{$userID}_NotificationType_{$activityType}_Preference_$preference";
                    if (
                        $activity["Notified"] === ActivityModel::SENT_SKIPPED &&
                        $activity["Emailed"] === ActivityModel::SENT_SKIPPED
                    ) {
                        // No point sending a notification if the user has opted out.
                        yield new LongRunnerSuccessID($longRunnerID);
                        continue;
                    }
                    $options += [
                        "NoDelete" => true,
                        "DisableFloodControl" => true,
                    ];
                    $result = $this->activityModel->save($activity, false, $options);

                    if (!$this->didActivitySave($result)) {
                        yield new LongRunnerFailedID($longRunnerID);
                    } else {
                        yield new LongRunnerSuccessID($longRunnerID);
                    }
                } catch (LongRunnerTimeoutException $e) {
                    return new LongRunnerNextArgs([
                        $activity,
                        $permissions,
                        $preference,
                        $userID,
                        $junctionTable,
                        $junctionID,
                    ]);
                } catch (Exception $e) {
                    yield new LongRunnerFailedID(
                        "User_{$userID}_NotificationType_{$activityType}_Preference_$preference"
                    );
                } finally {
                    $lastUserID = $userID;
                }
            }
        } while (!empty($usersToNotify));
        return LongRunner::FINISHED;
    }

    /**
     * Fetch the UserID of the users to notify.
     *
     * @param array|string $permissions
     * @param string $preference
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @param bool $hasDefaultPreferences
     * @param int|null $discussionID
     * @return Gdn_MySQLDriver
     */
    private function getUserToNotifyQuery(
        array|string $permissions,
        string $preference,
        ?string $junctionTable = null,
        ?int $junctionID = null,
        bool $hasDefaultPreferences = false,
        ?int $discussionID = null
    ): Gdn_MySQLDriver {
        $validRoleIDs = $this->permissionModel->getRoleIDsHavingSpecificPermission(
            $permissions,
            $junctionTable,
            $junctionID
        );

        if ($hasDefaultPreferences) {
            $sql = $this->database
                ->createSql()
                ->from("UserRole ur")
                ->join(
                    "UserMeta umEmail",
                    "umEmail.UserID = ur.UserID && umEmail.Name = 'Preferences.Email.$preference'",
                    "left"
                )
                ->join(
                    "UserMeta umPopup",
                    "umPopup.UserID = ur.UserID && umPopup.Name = 'Preferences.Popup.$preference'",
                    "left"
                )
                ->where([
                    "ur.RoleID" => $validRoleIDs,
                ]);
        } else {
            $preferences = ["Preferences.Popup.$preference.1", "Preferences.Email.$preference.1"];
            $sql = $this->database
                ->createSql()
                ->from("UserMeta um")
                ->join("UserRole ur", "um.UserID = ur.UserID", "inner")
                ->where([
                    "um.QueryValue" => $preferences,
                    "ur.RoleID" => $validRoleIDs,
                ]);
        }

        // Add the condition to exclude muted users if a discussion is provided.
        if ($discussionID !== null) {
            $sql->leftJoin(
                "UserDiscussion ud",
                "ud.UserID = ur.UserID AND ud.DiscussionID = {$discussionID} AND ud.Muted = 1"
            )->where("ud.UserID IS NULL");
        }

        return $sql;
    }

    /**
     * Get the total number of users that could be notified.
     *
     * @param array|string $permissions
     * @param string $preference
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @param bool $hasDefaultPreferences
     * @param int|null $discussionID
     * @return int
     * @throws Gdn_UserException
     */
    public function getUsersToNotifyIteratorCount(
        array|string $permissions,
        string $preference,
        ?string $junctionTable = null,
        ?int $junctionID = null,
        bool $hasDefaultPreferences = false,
        ?int $discussionID = null
    ): int {
        $query = $this->getUserToNotifyQuery(
            $permissions,
            $preference,
            $junctionTable,
            $junctionID,
            $hasDefaultPreferences,
            $discussionID
        );

        $query->select("distinct(ur.UserID)", "count", "RowCount");
        $sql = $query->getSelect(true);
        $result = $this->database->query($sql);
        $countData = $result->firstRow();
        return $countData->RowCount ?? 0;
    }

    /**
     * Fetch the users with their preferences.
     *
     * @param array|string $permissions
     * @param string $preference
     * @param int $lastUserID
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @param bool $hasDefaultPreferences
     * @param int|null $discussionID
     * @return array
     * @throws Exception
     */
    private function getUsersWithPreferences(
        array|string $permissions,
        string $preference,
        int $lastUserID,
        ?string $junctionTable = null,
        ?int $junctionID = null,
        bool $hasDefaultPreferences = false,
        ?int $discussionID = null
    ): array {
        $userToNotifyByID = [];

        $sql = $this->getUserToNotifyQuery(
            $permissions,
            $preference,
            $junctionTable,
            $junctionID,
            $hasDefaultPreferences,
            $discussionID
        );
        $sql->select("ur.UserID")
            ->where("ur.UserID >", $lastUserID)
            ->limit($this->batchSize)
            ->orderBy("ur.UserID", "asc");

        if ($hasDefaultPreferences) {
            $sql->select("umEmail.Value as Emailed, umPopup.Value as Notified");
            $defaultEmail = $this->config->get("Preferences.Email.$preference");
            $defaultNotified = $this->config->get("Preferences.Popup.$preference");
        } else {
            $sql->select("um.QueryValue as QueryValue");
        }

        $userPrefs = $sql->get()->resultArray();
        foreach ($userPrefs as $userPref) {
            $userID = $userPref["UserID"];
            if ($hasDefaultPreferences) {
                $userToNotifyByID[$userID]["Notified"] = $userPref["Notified"] ?? $defaultNotified;
                $userToNotifyByID[$userID]["Emailed"] = $userPref["Emailed"] ?? $defaultEmail;
            } else {
                $prefName = $userPref["QueryValue"];
                if (str_contains($prefName, ".Email.")) {
                    $userToNotifyByID[$userID]["Emailed"] = ActivityModel::SENT_PENDING;
                } elseif (str_contains($prefName, ".Popup.")) {
                    $userToNotifyByID[$userID]["Notified"] = ActivityModel::SENT_PENDING;
                }
            }
        }

        // Fetch the last user preferences in case one of its permissions missed the cut-off point.
        if (isset($userID)) {
            $userToNotifyByID[$userID] += $this->userMetaModel->getUserMeta($userID);
        }

        return $userToNotifyByID;
    }

    /**
     * Check if an activity was saved.
     *
     * @param mixed $activityResult
     * @return bool
     */
    public static function didActivitySave($activityResult): bool
    {
        return is_array($activityResult) && isset($activityResult["ActivityID"]);
    }
}

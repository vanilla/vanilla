<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

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
    /**
     * D.I.
     *
     * @param PermissionModel $permissionModel
     * @param Gdn_Database $database
     * @param UserModel $usermodel
     * @param UserMetaModel $userMetaModel
     * @param ActivityModel $activityModel
     */
    public function __construct(
        protected PermissionModel $permissionModel,
        protected Gdn_Database $database,
        protected UserModel $usermodel,
        protected UserMetaModel $userMetaModel,
        protected ActivityModel $activityModel,
        protected LongRunner $longRunner
    ) {
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
     * @return void
     */
    public function notify(
        array $activity,
        array|string $permissions,
        string $preference,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): void {
        $action = new LongRunnerAction(self::class, "notificationGenerator", [
            $activity,
            $permissions,
            $preference,
            0,
            $junctionTable,
            $junctionID,
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
     * @return Generator
     * @throws Exception
     */
    public function notificationGenerator(
        array $activity,
        array|string $permissions,
        string $preference,
        int $lastUserID = 0,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): Generator {
        $preferences = ["Preferences.Popup.$preference.1", "Preferences.Email.$preference.1"];
        $activityType = $activity["ActivityType"] ?? "unknown";
        // Yield total users to notify.
        yield new LongRunnerQuantityTotal(
            [$this, "getUsersToNotifyIteratorCount"],
            [$permissions, $preferences, $junctionTable, $junctionID]
        );

        // Grab all the users that need to be notified.
        $usersToNotify = $this->getUsersWithPreferences(
            $permissions,
            $preferences,
            $lastUserID,
            $junctionTable,
            $junctionID
        );

        // Start sending the notifications.
        foreach ($usersToNotify as $userID => $userPreferences) {
            try {
                if (!$this->usermodel->checkPermission($userID, $permissions)) {
                    continue;
                }

                $activity["NotifyUserID"] = $userID;
                $activity["Emailed"] =
                    $userPreferences["Emailed"] ?? false ? ActivityModel::SENT_PENDING : ActivityModel::SENT_SKIPPED;
                $activity["Notified"] =
                    $userPreferences["Notified"] ?? false ? ActivityModel::SENT_PENDING : ActivityModel::SENT_SKIPPED;

                $longRunnerID = "User_{$userID}_NotificationType_{$activityType}_Preference_$preference";
                if (
                    $activity["Notified"] === ActivityModel::SENT_SKIPPED &&
                    $activity["Emailed"] === ActivityModel::SENT_SKIPPED
                ) {
                    // No point sending a notification if the user has opted out.
                    yield new LongRunnerSuccessID($longRunnerID);
                    continue;
                }

                $result = $this->activityModel->save($activity, false, [
                    "NoDelete" => true,
                    "DisableFloodControl" => true,
                ]);

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
                yield new LongRunnerFailedID("User_{$userID}_NotificationType_{$activityType}_Preference_$preference");
            }
        }
        return LongRunner::FINISHED;
    }

    /**
     * Fetch the UserID of the users to notify.
     *
     * @param array|string $permissions
     * @param array $preferences
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @return Gdn_MySQLDriver
     */
    private function getUserToNotifyQuery(
        array|string $permissions,
        array $preferences,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): Gdn_MySQLDriver {
        $validRoleIDs = $this->permissionModel->getRoleIDsHavingSpecificPermission(
            $permissions,
            $junctionTable,
            $junctionID
        );
        $sql = $this->database
            ->createSql()
            ->from("UserMeta um")
            ->join("UserRole ur", "um.UserID = ur.UserID", "inner")
            ->where([
                "um.QueryValue" => $preferences,
                "ur.RoleID" => $validRoleIDs,
            ]);
        return $sql;
    }

    /**
     * Get the total number of users that could be notified.
     *
     * @param array|string $permissions
     * @param array $preferences
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @return int
     * @throws Gdn_UserException
     */
    public function getUsersToNotifyIteratorCount(
        array|string $permissions,
        array $preferences,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): int {
        $query = $this->getUserToNotifyQuery($permissions, $preferences, $junctionTable, $junctionID);
        $query->select("distinct(um.UserID)", "count", "RowCount");
        $sql = $query->getSelect(true);
        $result = $this->database->query($sql);
        $countData = $result->firstRow();
        return $countData->RowCount ?? 0;
    }

    /**
     * Fetch the users with their preferences.
     *
     * @param array|string $permissions
     * @param array $preferences
     * @param int $lastUserID
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @return array
     * @throws Exception
     */
    private function getUsersWithPreferences(
        array|string $permissions,
        array $preferences,
        int $lastUserID,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): array {
        $userToNotifyByID = [];

        $sql = $this->getUserToNotifyQuery($permissions, $preferences, $junctionTable, $junctionID);
        $sql->select("um.UserID, um.QueryValue")
            ->where("um.UserID >", $lastUserID)
            ->limit(100)
            ->orderBy("um.UserID", "asc");
        $userPrefs = $sql->get()->resultArray();

        foreach ($userPrefs as $userPref) {
            $userID = $userPref["UserID"];
            $prefName = $userPref["QueryValue"];
            if (str_contains($prefName, ".Email.")) {
                $userToNotifyByID[$userID]["Emailed"] = ActivityModel::SENT_PENDING;
            } elseif (str_contains($prefName, ".Popup.")) {
                $userToNotifyByID[$userID]["Notified"] = ActivityModel::SENT_PENDING;
            }
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

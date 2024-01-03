<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use UserMetaModel;
use UserModel;
use Vanilla\Community\Events\SubscriptionChangeEvent;
use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Forum\Digest\EmailDigestGenerator;
use Vanilla\Models\UserFragmentSchema;

/**
 * Model for user notification preferences.
 */
class UserNotificationPreferencesModel
{
    const PREFERENCE_USER_LANGUAGE = "NotificationLanguage";
    const NOTIFICATION_TYPES = ["email", "popup", "disabled"];

    /** @var UserMetaModel */
    private UserMetaModel $userMetaModel;

    /** @var UserModel */
    private UserModel $userModel;

    /** @var ActivityService */
    private ActivityService $activityService;

    private EventManager $eventManager;

    private \LocaleModel $localeModel;

    private \CategoryModel $categoryModel;

    /**
     * D.I.
     *
     * @param UserMetaModel $userMetaModel
     * @param UserModel $userModel
     * @param ActivityService $activityService
     * @param EventManager $eventManager
     * @param \LocaleModel $localeModel
     * @param \CategoryModel $categoryModel
     */
    public function __construct(
        UserMetaModel $userMetaModel,
        UserModel $userModel,
        ActivityService $activityService,
        EventManager $eventManager,
        \LocaleModel $localeModel,
        \CategoryModel $categoryModel
    ) {
        $this->userMetaModel = $userMetaModel;
        $this->userModel = $userModel;
        $this->activityService = $activityService;
        $this->eventManager = $eventManager;
        $this->localeModel = $localeModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * Get a user's notification preferences. This will return an array with the user's explicitly chosen preferences
     * combined with the default settings for preferences that haven't been explicitly set by the user.
     *
     * @param int $userID
     * @return array|int[]
     */
    public function getUserPrefs(int $userID): array
    {
        $notificationPreferenceNames = $this->activityService->getAllPreferences();
        $explicitPreferences = $this->getExplicitUserPreferences($userID);
        $result = [];

        /** @var class-string<Activity> $activity */
        foreach ($notificationPreferenceNames as $preferenceName) {
            // Don't even add the preference options if the preference is disabled.
            $isDisabled = Gdn::config("Preferences.Disabled.{$preferenceName}");
            if ($isDisabled) {
                continue;
            }

            // Set the user's preferences. If the default setting for a preference is 2 (a legacy setting that forces
            // notifications), we set it to 1, as we no longer allow forcing notifications.
            $emailDefault = Gdn::config("Preferences.Email." . $preferenceName, "0");
            $emailDefault = intval($emailDefault) > 0 ? "1" : $emailDefault;
            $popupDefault = Gdn::config("Preferences.Popup." . $preferenceName, "0");
            $popupDefault = intval($popupDefault) > 0 ? "1" : $popupDefault;

            // Populate preferences with the default value if a preference hasn't been explicitly chosen.
            $result["Email.{$preferenceName}"] = isset($explicitPreferences["Email.{$preferenceName}"])
                ? intval($explicitPreferences["Email.{$preferenceName}"])
                : $emailDefault;
            $result["Popup.{$preferenceName}"] = isset($explicitPreferences["Popup.{$preferenceName}"])
                ? intval($explicitPreferences["Popup.{$preferenceName}"])
                : $popupDefault;
        }

        // Return the preferences with integer values.
        $result = array_map(function ($pref) {
            return intval($pref);
        }, $result);

        //check for users language preference
        if (!empty($explicitPreferences[self::PREFERENCE_USER_LANGUAGE]) && $this->localeModel->hasMultiLocales()) {
            $result[self::PREFERENCE_USER_LANGUAGE] = $explicitPreferences[self::PREFERENCE_USER_LANGUAGE];
        }
        return $result;
    }

    /**
     * Get a user's explicitly selected preferences. Preferences from the UserMeta table take precedence
     * over preferences from the User table.
     *
     * @param int $userID
     * @return array
     */
    private function getExplicitUserPreferences(int $userID): array
    {
        $user = $this->userModel->getID($userID);
        $userPrefsFromUserTable = $user->Preferences ?? [];
        $userPrefsFromMetaTable = $this->userMetaModel->getUserMeta($userID, "Preferences.%", [], "Preferences.") ?? [];
        $allPrefs = array_merge($userPrefsFromUserTable, $userPrefsFromMetaTable);
        return $allPrefs;
    }

    /**
     * Update a user's preferences. Preferences are saved to both the User table and the UserMeta table.
     *
     * @param int $userID
     * @param array $userPrefs
     * @return array|int[]
     */
    public function save(int $userID, array $userPrefs): array
    {
        $existingPrefs = $this->getUserPrefs($userID);
        $allPreferences = $this->activityService->getAllPreferences();

        $prefsToSave = [];

        foreach ($userPrefs as $key => $val) {
            if ($key == self::PREFERENCE_USER_LANGUAGE) {
                if ($existingPrefs["self::PREFERENCE_USER_LANGUAGE"] ?? null != $val) {
                    $prefsToSave[$key] = $val;
                    $this->userMetaModel->setUserMeta($userID, "Preferences." . $key, $val);
                }
            } else {
                $parts = explode(".", $key);
                $notificationMethod = $parts[0];
                if (!in_array(strtolower($notificationMethod), ["email", "popup"])) {
                    throw new NotFoundException("{$notificationMethod} is not a valid notification method. It
                must be one of: Email, Popup");
                }

                $activitiesPerPreference = $this->activityService->getActivitiesByPreference($parts[1]);

                if (empty($activitiesPerPreference)) {
                    throw new NotFoundException("{$parts[1]} is not a valid preference.");
                }
                if ($val != $existingPrefs[$key]) {
                    $prefsToSave[$key] = intval($val);
                    $this->userMetaModel->setUserMeta($userID, "Preferences." . $key, intval($val));
                }
            }
        }

        $this->userModel->savePreference($userID, $prefsToSave);
        if (array_key_exists("Email.DigestEnabled", $prefsToSave)) {
            if ($prefsToSave["Email.DigestEnabled"] == 1) {
                // The user has enabled the digest. We now need to forcibly enable digests for all the user's followed categories
                $userFollowedCategories = array_keys($this->categoryModel->getFollowed($userID));
                if (!empty($userFollowedCategories)) {
                    //Add preferences to userMeta
                    foreach ($userFollowedCategories as $categoryID) {
                        $preferenceKey = sprintf(\CategoryModel::PREFERENCE_DIGEST_EMAIL, $categoryID);
                        $currentPreference = $this->userMetaModel->getUserMeta($userID, $preferenceKey);
                        //Not set or currently disabled
                        if (empty($currentPreference[$preferenceKey])) {
                            //enable the digest
                            $this->userMetaModel->setUserMeta($userID, $preferenceKey, 1);
                        }
                    }
                    //update UserCategory table to enable digest flag for all user's followed categories
                    $this->categoryModel->SQL
                        ->update(
                            "UserCategory",
                            ["DigestEnabled" => 1],
                            [
                                "UserID" => $userID,
                                "CategoryID" => $userFollowedCategories,
                                "Followed" => 1,
                            ]
                        )
                        ->put();
                    //Flush current user cache
                    \CategoryModel::clearUserCache($userID);
                    Gdn::cache()->remove("Follow_{$userID}");
                }
            }
            $action =
                $prefsToSave["Email.DigestEnabled"] == 1
                    ? SubscriptionChangeEvent::ACTION_DIGEST_ENABLED
                    : SubscriptionChangeEvent::ACTION_DIGEST_DISABLED;
            $sender = Gdn::userModel()->currentFragment();
            $senderSchema = new UserFragmentSchema();
            $sender = $senderSchema->validate($sender);
            $totalDigestSubscribers = Gdn::getContainer()
                ->get(EmailDigestGenerator::class)
                ->getDigestEnabledUsersCount();
            $digestSubscriptionChangeEvent = new SubscriptionChangeEvent(
                SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
                [
                    $action,
                    "subscriptionChange" => [
                        "subscription" =>
                            ($prefsToSave["Email.DigestEnabled"] == 1 ? t("Enabled") : t("Disabled")) .
                            " " .
                            t("Email Digest"),
                        "type" => $action,
                        "digestSubscribers" => $totalDigestSubscribers,
                    ],
                ],
                $sender
            );
            $this->eventManager->dispatch($digestSubscriptionChangeEvent);
        }
        return $this->getUserPrefs($userID);
    }

    /**
     * Get the site-wide notification preference defaults.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        $defaultPreferences = [];

        foreach ($this->activityService->getActivityTypeIDs() as $activityType) {
            /** @var Activity $activity */
            $activity = $this->activityService->getActivityByTypeID($activityType);
            if (!$activity::ALLOW_DEFAULT_PREFERENCE) {
                continue;
            }

            $activityPreference = $activity::getPreference();
            $isDisabled = Gdn::config()->get("Preferences.Disabled.{$activityPreference}", 0);
            $defaultPreferences["Disabled.{$activityPreference}"] = $isDisabled;
            $defaultPreferences["Email.{$activityPreference}"] = $isDisabled
                ? 0
                : Gdn::config()->get("Preferences.Email.{$activityPreference}", 0);
            $defaultPreferences["Popup.{$activityPreference}"] = $isDisabled
                ? 0
                : Gdn::config()->get("Preferences.Popup.{$activityPreference}", 0);
        }

        return $defaultPreferences;
    }

    /**
     * Save site-wide notification preference default values.
     *
     * @param array $updatedDefaults
     * @return bool|int
     * @throws NotFoundException
     */
    public function saveDefaults(array $updatedDefaults)
    {
        $defaultsToSave = [];
        foreach ($updatedDefaults as $key => $value) {
            $keyParts = explode(".", $key);
            if (!in_array($keyParts[1], $this->activityService->getAllPreferences())) {
                throw new NotFoundException("'{$keyParts[1]}' is not a valid preference.");
            }

            $activitiesPerPreference = $this->activityService->getActivitiesByPreference($keyParts[1]);

            /** @var Activity $activity */
            foreach ($activitiesPerPreference as $activity) {
                if (!$activity::ALLOW_DEFAULT_PREFERENCE) {
                    throw new ForbiddenException(
                        "You cannot set a default preference for the {$activity::getActivityTypeID()} activity."
                    );
                }
            }

            if (!in_array(strtolower($keyParts[0]), ["email", "popup", "disabled"])) {
                $setting = strtolower($keyParts[0]);
                throw new NotFoundException("'{$setting}' is not a valid default notification method. It
                must be one of: email, popup, disabled");
            }
            $defaultsToSave["Preferences.{$key}"] = $value;
        }
        $result = Gdn::config()->saveToConfig($defaultsToSave);
        return $result;
    }
}

<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use ActivityModel;
use CategoryModel;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use UserMetaModel;
use UserModel;
use Vanilla\Dashboard\Models\ActivityService;

/**
 * Unsubscribe model.
 */
class UnsubscribeModel
{
    /** @var array|mixed */
    private $metaPrefs;

    /** @var UserMetaModel*/
    private $userMetaModel;

    /** @var CategoryModel*/
    private $categoryModel;

    /** @var ActivityService */
    private $activityService;

    /** @var UserModel */
    private $userModel;

    /** @var ActivityModel */
    private $activityModel;

    /**
     * Constructor
     *
     * @param UserMetaModel $userMetaModel
     * @param ActivityService $activityService;
     * @param CategoryModel $categoryModel
     * @param UserModel $userModel
     * @param ActivityModel $activityModel
     */
    public function __construct(
        UserMetaModel $userMetaModel,
        ActivityService $activityService,
        CategoryModel $categoryModel,
        UserModel $userModel,
        ActivityModel $activityModel
    ) {
        $this->userMetaModel = $userMetaModel;
        $this->activityService = $activityService;
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->activityModel = $activityModel;
    }

    /**
     * Process unsubscribe request.
     *
     * @param array $followedCategory preferences to process.
     *
     * @return array
     * @throws NotFoundException
     * @throws ForbiddenException|\Throwable
     */
    public function unfollowCategory(array $followedCategory): array
    {
        if (str_contains($followedCategory["preference"], "Digest")) {
            $this->categoryModel->setPreferences($followedCategory["userID"], $followedCategory["categoryID"], [
                CategoryModel::stripCategoryPreferenceKey(CategoryModel::PREFERENCE_DIGEST_EMAIL) => $followedCategory[
                    "enabled"
                ],
            ]);
        } else {
            $this->userMetaModel->setUserMeta(
                $followedCategory["userID"],
                $followedCategory["preference"],
                $followedCategory["enabled"]
            );
        }
        return $followedCategory;
    }

    /**
     * Process re-unsubscribe request.
     *
     * @param array $preference Preference array to update.
     * @param string $enabled set "1" or "0" for preferences.
     *
     * @return array
     * @throws NotFoundException|\Throwable
     */
    public function updateNotificationPreferences(array $preference, string $enabled): array
    {
        $this->userModel->savePreference($preference["userID"], $preference["preference"], $enabled);
        $this->userMetaModel->setUserMeta($preference["userID"], "Preferences." . $preference["preference"], $enabled);
        $preference["enabled"] = $enabled;

        return $preference;
    }

    /**
     * Check permission and load user/activity information.
     *
     * @param string $token
     * @param bool|null $enabled
     *
     * @return array
     * @throws NotFoundException
     */
    public function validateAccess(string $token, string $enabled = null): array
    {
        $activityInfo = $this->activityModel->decodeNotificationToken($token);
        $reasons = empty($activityInfo["reason"]) ? [] : explode(", ", $activityInfo["reason"]);
        $user = $this->userModel->getID($activityInfo["notifyUserID"], DATASET_TYPE_ARRAY);
        $this->metaPrefs = $this->userMetaModel->getUserMeta(
            $activityInfo["notifyUserID"],
            "Preferences.%",
            [],
            "Preferences."
        );
        $activityInfo["data"] = (array) $activityInfo["data"];

        if (isset($activityInfo["data"]["category"]) && ($key = array_search("advanced", $reasons)) !== false) {
            unset($reasons[$key]);
            $activityType = $activityInfo["activityType"];
            if ($activityType != "Digest") {
                $activityType = "New" . (str_contains($activityType, "Comment") ? "Comment" : "Discussion");
            }
            $reasons[] = "FollowedCategory:" . $activityInfo["data"]["category"] . ":Email.{$activityType}";
        }

        if ($activityInfo["activityType"] == "Comment") {
            if (in_array("mine", $reasons)) {
                $reasons[] = "DiscussionComment";
            } elseif (in_array("participated", $reasons)) {
                $reasons[] = "ParticipateComment";
            }
        } else {
            $reasons[] = $activityInfo["activityType"];
        }

        $reasons = array_unique(array_merge($reasons, $activityInfo["ActivityTypeList"]));
        $result = [];
        foreach ($reasons as $reason) {
            if (str_starts_with($reason, "FollowedCategory:")) {
                $categoryInfo = explode(":", $reason);
                $category = $this->categoryModel->searchByName($categoryInfo[1], null)[0];
                $reason = "$categoryInfo[2]." . $category["CategoryID"];
                if ($enabled === null || val($reason, $this->metaPrefs, 0) == $enabled) {
                    $result[$categoryInfo[0]] = [
                        "categoryID" => $category["CategoryID"],
                        "preference" => "Preferences.$categoryInfo[2].{$category["CategoryID"]}",
                        "name" => $category["Name"],
                        "enabled" => val($reason, $this->metaPrefs, 0),
                        "userID" => $user["UserID"],
                    ];
                }
            } else {
                $reason = $this->activityService->getPreferenceByActivityType($reason) ?? $reason;
                $reason = "Email." . $reason;
                $validateReason = val($reason, $this->metaPrefs, null) ?? val($reason, $user["Preferences"], null);
                if ($validateReason !== null && ($enabled === null || $validateReason == $enabled)) {
                    if (!isset($result[$reason])) {
                        $result[$reason] = [
                            "preference" => $reason,
                            "enabled" => $enabled,
                            "userID" => $user["UserID"],
                        ];
                    }
                }
            }
        }
        return $result;
    }
}

<?php

/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Web\Controller;

/**
 * Endpoints for managing addons.
 */
class UnsubscribeApiController extends Controller
{
    /** @var array  */
    private array $user;

    /** @var array  */
    private array $activityInfo;

    /** @var array|mixed */
    private $metaPrefs;

    /** @var UserMetaModel*/
    private $userMetaModel;

    /**
     * Constructor
     *
     * @param UserMetaModel $userMetaModel
     */
    public function __construct(UserMetaModel $userMetaModel)
    {
        $this->userMetaModel = $userMetaModel;
    }

    /**
     * Process unsubscribe request.
     *
     * @param string $token String token.
     * @return array
     * @throws NotFoundException
     */
    public function post(string $token): array
    {
        $result = $this->validateAccess($token, "1");

        // Unset permissions if there is only 1 reason for email notification.
        if (count($result) == 1) {
            if (isset($result["FollowedCategory"])) {
                if ($result["FollowedCategory"]) {
                    $result["FollowedCategory"]["enabled"] = "0";
                    $this->userMetaModel->setUserMeta(
                        $this->user["UserID"],
                        $result["FollowedCategory"]["preference"],
                        $result["FollowedCategory"]["enabled"]
                    );
                }
            } else {
                $result[0]["enabled"] = "0";
                Gdn::userModel()->savePreference($this->user["UserID"], $result[0]["preference"], "0");
                $this->userMetaModel->setUserMeta(
                    $this->user["UserID"],
                    "Preferences." . $result[0]["preference"],
                    "0"
                );
            }
        }
        $follow = [];
        if (isset($result["FollowedCategory"])) {
            $follow = $result["FollowedCategory"];
            unset($result["FollowedCategory"]);
        }
        return ["preferences" => $result, "followCategory" => $follow];
    }

    /**
     * Process re-unsubscribe request.
     *
     * @param string $token String token.
     * @return array
     * @throws NotFoundException
     */
    public function post_resubscribe(string $token): array
    {
        $result = $this->validateAccess($token, "0");

        // Unset permissions if there is only 1 reason for email notification.
        if (count($result) == 1) {
            if (isset($result["FollowedCategory"])) {
                $result["FollowedCategory"]["enabled"] = "1";
                $this->userMetaModel->setUserMeta(
                    $this->user["UserID"],
                    $result["FollowedCategory"]["preference"],
                    $result["FollowedCategory"]["enabled"]
                );
            } else {
                $result[0]["enabled"] = "1";
                Gdn::userModel()->savePreference($this->user["UserID"], $result[0]["preference"], "1");
                $this->userMetaModel->setUserMeta(
                    $this->user["UserID"],
                    "Preferences." . $result[0]["preference"],
                    "1"
                );
            }
        }
        $follow = [];
        if (isset($result["FollowedCategory"])) {
            $follow = $result["FollowedCategory"];
            unset($result["FollowedCategory"]);
        }
        return ["preferences" => $result, "followCategory" => $follow];
    }

    /**
     * Process unsubscribe request.
     *
     * @param string $token String token.
     * @param array $body Unsubscribe body.
     * @return array
     */
    public function patch(string $token, array $body): array
    {
        $reasons = $this->validateAccess($token);
        $result = [];
        $in = $this->schema(
            [
                "preferences:a" => ["preference:s", "enabled:s"],
                "followCategory:o?" => ["categoryID:i", "preference:s", "name:s?", "enabled:s"],
            ],
            "in"
        )->setDescription("Update a notification.");

        $body = $in->validate($body);
        $sentPreferences = $body["preferences"];
        $followCategory = $body["followCategory"] ?? null;
        $follow = $reasons["FollowedCategory"] ?? null;
        if ($followCategory != null && $follow != null && $follow["enabled"] != $followCategory["enabled"]) {
            unset($reasons["FollowedCategory"]);
            if ($followCategory["preference"] == "follow") {
                $categoryModel = Gdn::getContainer()->get(CategoryModel::class);
                $categoryModel->setPreferences($this->user["UserID"], $followCategory["categoryID"], [
                    CategoryModel::stripCategoryPreferenceKey(CategoryModel::PREFERENCE_FOLLOW) => "1",
                ]);
            } else {
                $this->userMetaModel->setUserMeta(
                    $this->user["UserID"],
                    $followCategory["preference"],
                    $followCategory["enabled"]
                );
            }
        }

        foreach ($reasons as &$preference) {
            $enabled = $this->getPreferences($sentPreferences, $preference["preference"]);

            if ($enabled !== null && $enabled !== $preference["enabled"]) {
                $result[$preference["preference"]] = $enabled;
                $preference["enabled"] = $enabled;

                $this->userMetaModel->setUserMeta(
                    $this->user["UserID"],
                    "Preferences." . $preference["preference"],
                    $enabled
                );
            }
        }

        if (count($result) > 0) {
            Gdn::userModel()->savePreference($this->user["UserID"], $result);
        }

        return ["preferences" => $reasons, "followCategory" => $follow];
    }

    /**
     * Get selected Preference
     *
     * @param array $preferences Data sent from the front end.
     * @param string $reason Reason to look for.
     *
     * @return mixed|void
     */
    public function getPreferences(array $preferences, string $reason)
    {
        foreach ($preferences as $pref) {
            if ($reason == $pref["preference"]) {
                return $pref["enabled"];
            }
        }
        return null;
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
        if (!Gdn::config(ActivityModel::UNSUBSCRIBE_LINK)) {
            throw new NotFoundException();
        }
        $activityModel = new ActivityModel();
        $this->activityInfo = $activityModel->decodeNotificationToken($token);
        $reasons = empty($this->activityInfo["reason"]) ? [] : explode(", ", $this->activityInfo["reason"]);
        $this->user = Gdn::userModel()->getID($this->activityInfo["notifyUserID"], DATASET_TYPE_ARRAY);
        $this->metaPrefs = $this->userMetaModel->getUserMeta(
            $this->activityInfo["notifyUserID"],
            "Preferences.%",
            [],
            "Preferences."
        );

        if (isset($this->activityInfo["data"]["category"]) && ($key = array_search("advanced", $reasons)) !== false) {
            unset($reasons[$key]);
            $reasons[] =
                "FollowedCategory:" .
                $this->activityInfo["data"]["category"] .
                ":New{$this->activityInfo["activityType"]}";
        } else {
            if ($this->activityInfo["activityType"] == "Comment") {
                if (in_array("mine", $reasons)) {
                    $reasons[] = "DiscussionComment";
                } elseif (in_array("participated", $reasons)) {
                    $reasons[] = "ParticipateComment";
                }
            } else {
                $reasons[] = $this->activityInfo["activityType"];
            }
        }
        $reasons = array_merge($reasons, $this->activityInfo["ActivityTypeList"]);
        $reasons = array_unique($reasons);
        $result = [];
        $categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        foreach ($reasons as $reason) {
            if (str_starts_with($reason, "FollowedCategory:")) {
                $categoryInfo = explode(":", $reason);
                $category = $categoryModel->searchByName($categoryInfo[1], null)[0];
                $reason = "Email.$categoryInfo[2]." . $category["CategoryID"];
                if ($enabled === null || val($reason, $this->metaPrefs, 0) == $enabled) {
                    $result[$categoryInfo[0]] = [
                        "categoryID" => $category["CategoryID"],
                        "preference" => "Preferences.Email.$categoryInfo[2].{$category["CategoryID"]}",
                        "name" => $category["Name"],
                        "enabled" => $enabled,
                    ];
                }
            } else {
                $reason = "Email." . $reason;
                $currectValue = val($reason, $this->metaPrefs, null) ?? val($reason, $this->user["Preferences"], null);
                if ($currectValue !== null && ($enabled === null || $currectValue == $enabled)) {
                    $result[] = ["preference" => $reason, "enabled" => $enabled];
                }
            }
        }
        return $result;
    }
}

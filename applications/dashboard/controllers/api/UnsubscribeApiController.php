<?php

/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\NotFoundException;
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
    /**
     * Process unsubscribe request.
     *
     * @param string $token String token.
     * @return array
     * @throws NotFoundException
     */
    public function get(string $token): array
    {
        $reasons = $this->validateAccess($token);
        $result = [];
        foreach ($reasons as $reason) {
            $reason = "Email." . $reason;
            $pref = val($reason, $this->user["Preferences"], null);
            if ($pref == 1) {
                $result[$reason] = true;
            }
        }
        // Unset permissions if there is only 1 reason for email notification.
        if (count($result) == 1) {
            foreach ($result as $perf => $value) {
                $result[$perf] = "0";
            }
            Gdn::userModel()->savePreference($this->activityInfo["notifyUserID"], $result);
            $result = [];
        }

        return ["preferences" => $result];
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
                "preferences:a" => ["items" => "string"],
            ],
            "in"
        )->setDescription("Update a notification.");

        $body = $in->validate($body);
        $preferences = $body["preferences"];
        foreach ($reasons as $reason) {
            $reason = "Email." . $reason;
            $pref = val($reason, $this->user["Preferences"], null);
            if ($pref == 1 && in_array($reason, $preferences)) {
                $result[$reason] = "0";
            }
        }
        if (count($result) > 0) {
            Gdn::userModel()->savePreference($this->activityInfo["notifyUserID"], $result);
        }
        return ["preferences" => $result];
    }

    /**
     * Check permission and load user/activity information.
     *
     * @param string $token
     *
     * @return array
     * @throws NotFoundException
     */
    public function validateAccess(string $token): array
    {
        if (!Gdn::config(ActivityModel::UNSUBSCRIBE_LINK)) {
            throw new NotFoundException();
        }
        $activityModel = new ActivityModel();
        $this->activityInfo = $activityModel->decodeNotificationToken($token);
        $reasons = empty($this->activityInfo["reason"]) ? [] : explode(",", $this->activityInfo["reason"]);
        $this->user = Gdn::userModel()->getID($this->activityInfo["notifyUserID"], DATASET_TYPE_ARRAY);
        $type = $activityModel->getActivityType($this->activityInfo["activityTypeID"]);
        $reasons[] = val("Name", $type);
        return $reasons;
    }
}

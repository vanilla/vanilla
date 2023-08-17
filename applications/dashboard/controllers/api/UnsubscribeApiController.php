<?php

/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\UnsubscribeModel;
use Vanilla\Web\Controller;

/**
 * Endpoints for unsubscribe from notification/digest.
 */
class UnsubscribeApiController extends Controller
{
    /** @var UnsubscribeModel*/
    private $unsubscribeModel;

    /**
     * Constructor
     *
     * @param UnsubscribeModel $unsubscribeModel
     */
    public function __construct(UnsubscribeModel $unsubscribeModel)
    {
        $this->unsubscribeModel = $unsubscribeModel;
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
        $result = $this->unsubscribeModel->validateAccess($token, "1");
        $follow = $result["FollowedCategory"] ?? [];
        // Unset permissions if there is only 1 reason for email notification.
        if (count($result) == 1) {
            if (count($follow) > 0) {
                $follow["enabled"] = "0";
                $follow = $this->unsubscribeModel->unfollowCategory($follow);
            } else {
                $result[0] = $this->unsubscribeModel->updateNotificationPreferences($result[0], "0");
            }
        }
        unset($result["FollowedCategory"]);
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
        $result = $this->unsubscribeModel->validateAccess($token, "0");
        $follow = $result["FollowedCategory"] ?? [];
        // Unset permissions if there is only 1 reason for email notification.
        if (count($result) == 1) {
            if (count($follow) > 0) {
                $follow["enabled"] = "1";
                $follow = $this->unsubscribeModel->unfollowCategory($follow);
            } else {
                $result[0] = $this->unsubscribeModel->updateNotificationPreferences($result[0], "1");
            }
        }
        unset($result["FollowedCategory"]);
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
        $reasons = $this->unsubscribeModel->validateAccess($token);
        $result = [];
        $in = $this->schema(
            [
                "preferences:a?" => ["preference:s", "enabled:s"],
                "followCategory:o?" => ["categoryID:i", "preference:s", "name:s?", "enabled:s"],
            ],
            "in"
        )->setDescription("Update a notification.");

        $body = $in->validate($body);
        $sentPreferences = $body["preferences"] ?? null;
        $followCategory = $body["followCategory"] ?? null;
        $follow = $reasons["FollowedCategory"] ?? [];
        unset($reasons["FollowedCategory"]);
        if ($followCategory != null && count($follow) > 1 && $follow["enabled"] != $followCategory["enabled"]) {
            $follow["enabled"] = $followCategory["enabled"];
            $follow = $this->unsubscribeModel->unfollowCategory($follow);
        }
        if ($sentPreferences != null) {
            foreach ($reasons as &$preference) {
                $enabled = $this->getPreferences($sentPreferences, $preference["preference"]);

                if ($enabled !== null && $enabled !== $preference["enabled"]) {
                    $preference = $this->unsubscribeModel->updateNotificationPreferences($preference, $enabled);
                }
            }
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
}

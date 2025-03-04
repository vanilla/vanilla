<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\ForbiddenException;
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
     * @throws Throwable
     * @throws ForbiddenException
     */
    public function post(string $token): array
    {
        $result = $this->unsubscribeModel->validateAccess($token, "1");
        if ($this->isUnsubscribeRequestProcessed($result)) {
            return $result;
        }
        $count = count($result);
        $follow = $result["FollowedCategory"] ?? [];
        // Check for any follow content from plugins
        $followContent = $result["followContent"] ?? [];
        unset($result["FollowedCategory"], $result["followContent"]);
        $result = array_values($result);
        // Unset permissions if there is only 1 reason for email notification.
        if ($count == 1) {
            if (count($follow) > 0) {
                $follow["enabled"] = "0";
                $follow = $this->unsubscribeModel->unfollowCategory($follow);
            } else {
                $result[0] = $this->unsubscribeModel->updateNotificationPreferences($result[0], "0");
            }
        }
        // Assemble the data to be returned
        $data = [
            "preferences" => $result,
        ];
        if (!empty($followContent)) {
            $data["followContent"] = $followContent;
        } else {
            $data["followCategory"] = $follow;
        }
        return $data;
    }

    /**
     * Process re-unsubscribe request.
     *
     * @param string $token String token.
     * @return array
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function post_resubscribe(string $token): array
    {
        $result = $this->unsubscribeModel->validateAccess($token, "0");
        if ($this->isUnsubscribeRequestProcessed($result)) {
            return $result;
        }
        $count = count($result);
        $follow = $result["FollowedCategory"] ?? [];
        unset($result["FollowedCategory"]);
        $result = array_values($result);
        // Unset permissions if there is only 1 reason for email notification.
        if ($count == 1) {
            if (count($follow) > 0) {
                $follow["enabled"] = "1";
                $follow = $this->unsubscribeModel->unfollowCategory($follow);
            } else {
                $result[0] = $this->unsubscribeModel->updateNotificationPreferences($result[0], "1");
            }
        }

        return ["preferences" => $result, "followCategory" => $follow];
    }

    /**
     * Check if the unsubscibe request has been already processed
     * @param array $result
     * @return bool
     */
    private function isUnsubscribeRequestProcessed(array &$result): bool
    {
        if (isset($result["processed"]) && $result["processed"] == "1") {
            unset($result["processed"]);
            return true;
        }
        return false;
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
        $in = $this->schema(
            [
                "preferences:a?" => ["preference:s", "enabled:s"],
                "followCategory:o?" => ["categoryID:i", "preference:s", "name:s?", "enabled:s"],
            ],
            ["UnsubscribePatch", "in"]
        )->setDescription("Update a notification.");

        $body = $in->validate($body);
        $data = [];
        $sentPreferences = $body["preferences"] ?? null;
        $followCategory = $body["followCategory"] ?? null;
        $follow = $reasons["FollowedCategory"] ?? [];
        if (!empty($body["followContent"])) {
            $followContent = $body["followContent"];
            $data = $this->getEventManager()->fireFilter("unsubscribe_patch", $data, $followContent);
        }
        unset($reasons["FollowedCategory"], $reasons["followContent"]);
        $reasons = array_values($reasons);
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
        $data["preferences"] = $reasons;
        if (!empty($follow)) {
            $data["followCategory"] = $follow;
        }
        return $data;
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

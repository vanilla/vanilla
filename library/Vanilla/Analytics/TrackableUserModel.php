<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Web\Data;
use UserModel;

/**
 * Model for creating trackable user instances.
 */
class TrackableUserModel
{
    use TrackableDecoratorTrait;

    /** @var UserModel */
    private $userModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var TrackableDecoratorInterface[] */
    private $userDecorators = [];

    /**
     * DI.
     *
     * @param UserModel $userModel
     * @param \Gdn_Session $session
     */
    public function __construct(UserModel $userModel, \Gdn_Session $session)
    {
        $this->userModel = $userModel;
        $this->session = $session;
    }

    /**
     * @param TrackableDecoratorInterface $decorator
     */
    public function addUserDecorator(TrackableDecoratorInterface $decorator): void
    {
        $this->userDecorators[] = $decorator;
    }

    /**
     * Retrieve information about a particular user for user in analytics.
     *
     * @param int $userID Record ID of the user to fetch.
     * @param  bool $isGuestCollection Will this data be used in a collection that contains guest data?
     * @return array An array representing the user data on success, false on failure.
     */
    public function getTrackableUser(int $userID, bool $isGuestCollection = false): array
    {
        $user = $this->userModel->getID($userID);
        $roles = [];
        $roleIDs = [];
        $roleNames = [];

        if ($user) {
            /**
             * Fetch the target user's roles.  If we have any (and we should), iterate through them and grab the
             * relevant attributes.
             */
            $userRoles = $this->userModel->getRoles($userID);
            if ($userRoles->count() > 0) {
                foreach ($userRoles->resultObject() as $currentRole) {
                    $roles[] = [
                        "name" => $currentRole->Name,
                        "roleID" => $currentRole->RoleID,
                    ];
                    $roleIDs[] = $currentRole->RoleID;
                    $roleNames[] = $currentRole->Name;
                }

                usort($roles, function ($a, $b) {
                    return $a["roleID"] <=> $b["roleID"];
                });
            }

            $roleType = self::getRoleType((array) $user);

            $userInfo = [
                "commentCount" => (int) $user->CountComments,
                "dateFirstVisit" => TrackableDateUtils::getDateTime($user->DateFirstVisit),
                "dateRegistered" => TrackableDateUtils::getDateTime($user->DateInserted),
                "discussionCount" => (int) $user->CountDiscussions,
                "name" => $user->Name,
                "roles" => $roles,
                "roleIDs" => $roleIDs,
                "roleNames" => $roleNames,
                "roleType" => $roleType,
                "userID" => (int) $user->UserID,
                "url" => userUrl($user),

                // Kludge because this has always been set, even if the addon is disabled.
                "rank" => [
                    "rankID" => 0,
                ],
            ];

            $timeFirstVisit = strtotime($user->DateFirstVisit) ?: 0;
            $timeRegistered = strtotime($user->DateInserted) ?: 0;

            $userInfo["sinceFirstVisit"] = time() - $timeFirstVisit;
            $userInfo["sinceRegistered"] = time() - $timeRegistered;

            $userInfo["points"] = val("Points", $user, 0);

            // Apply decorators.
            $boxedUserData = new Data($userInfo, ["isGuestCollection" => $isGuestCollection]);
            $userInfo = $this->applyDecorators($boxedUserData, $this->userDecorators);
            return $userInfo;
        } else {
            // Fallback user data
            return [
                "userID" => 0,
                "name" => "@notfound",
            ];
        }
    }

    /**
     * Build and return guest data for the current user.
     *
     * @return array An array representing analytics data for the current user as a guest.
     */
    public function getGuest(): array
    {
        $userInfo = [
            "dateFirstVisit" => null,
            "name" => "@guest",
            "roleType" => "guest",
            "userID" => 0,
        ];
        $boxedUserData = new Data($userInfo, ["isGuestCollection" => true]);
        $userInfo = $this->applyDecorators($boxedUserData, $this->userDecorators);

        return $userInfo;
    }

    /**
     * Build an array of analytics data for the current user, based on whether they are a logged-in user.
     *
     * @param  bool $isGuestCollection Will this data be used in a collection that contains guest data?
     * @return array
     */
    public function getCurrentUser(bool $isGuestCollection = false): array
    {
        return $this->session->isValid()
            ? $this->getTrackableUser($this->session->UserID, $isGuestCollection)
            : $this->getGuest();
    }

    /**
     * Build and return anonymized user data.
     *
     * @param array $user Analytics user details to be anonymized.
     * @return array
     *@see getTrackableUser
     */
    public static function anonymizeUser(array $user): array
    {
        return [
            "dateFirstVisit" => null,
            "name" => "@anonymous",
            "roleType" => $user["roleType"] ?? null,
            "userID" => -1,
        ];
    }

    /**
     * Get a user's role type.
     *
     * @param array $user A user row.
     * @return string
     */
    private function getRoleType(array $user): string
    {
        if ($this->userModel->checkPermission($user, "Garden.Settings.Manage")) {
            $roleType = "admin";
        } elseif ($this->userModel->checkPermission($user, "Garden.Community.Manage")) {
            $roleType = "cm";
        } elseif ($this->userModel->checkPermission($user, "Garden.Moderation.Manage")) {
            $roleType = "mod";
        } else {
            $roleType = "member";
        }

        return $roleType;
    }

    /**
     * Generate a random universally unique identifier.
     *
     * @link https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
     * @link http://php.net/manual/en/function.uniqid.php#94959
     * @return string A UUIDv4-compliant string
     */
    public static function uuid(): string
    {
        return sprintf(
            "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

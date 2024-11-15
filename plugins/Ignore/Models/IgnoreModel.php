<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Ignore\Models;

/**
 * Model for the Ignore plugin.
 */
class IgnoreModel
{
    /**
     * DI.
     */
    public function __construct(
        private \UserMetaModel $userMetaModel,
        private \UserModel $userModel,
        private \Gdn_Session $session
    ) {
    }

    /**
     * Return ignored userIDs with their ignore dates.
     *
     * @param int|null $userID Defaults to session user.
     *
     * @return array<int, string> Mapping of UserID => Date
     */
    public function getIgnoredUserIDsWithDates(?int $userID = null): array
    {
        if (!$this->session->isValid()) {
            return [];
        }

        $ignoredUsersRaw = $this->userMetaModel->getUserMeta(
            $userID ?? $this->session->UserID,
            "Plugin.Ignore.Blocked.User.%"
        );
        $ignoreUserToDate = [];
        foreach ($ignoredUsersRaw as $ignoredUsersKey => $ignoredUsersIgnoreDate) {
            $ignoredUsersKeyArray = explode(".", $ignoredUsersKey);
            $ignoredUsersID = array_pop($ignoredUsersKeyArray);
            $ignoreUserToDate[$ignoredUsersID] = $ignoredUsersIgnoreDate;
        }

        return $ignoreUserToDate;
    }

    /**
     * Get ignored user records.
     *
     * @param int|null $userID Defaults to session user.
     *
     * @return array<int>
     */
    public function getIgnoredUserIDs(?int $userID = null): array
    {
        $ignoredUsersIDs = $this->getIgnoredUserIDsWithDates($userID);
        return array_keys($ignoredUsersIDs);
    }

    /**
     * @param int|null $userID Defaults to session user.
     *
     * @return array
     */
    public function getIgnoredUserFragments(?int $userID = null): array
    {
        $ignoredUserWithDate = $this->getIgnoredUserIDsWithDates($userID);

        $ignoredUsers = $this->userModel->getUserFragments(array_keys($ignoredUserWithDate));

        // Add ignore date to each user
        foreach ($ignoredUsers as $ignoredUsersID => &$ignoredUser) {
            $ignoredUser->addExtraData([
                "dateIgnored" => new \DateTime($ignoredUserWithDate[$ignoredUsersID]),
            ]);
        }
        $ignoredUsers = array_values($ignoredUsers);
        return $ignoredUsers;
    }

    /**
     * @param int|null $userID Defaults to session user.
     *
     * @return array<int, array> Mapping of UserID => User
     */
    public function getIgnoredUserRecords(?int $userID = null): array
    {
        $ignoredUserWithDate = $this->getIgnoredUserIDsWithDates($userID);

        $ignoredUsers = $this->userModel->getIDs(array_keys($ignoredUserWithDate));

        // Add ignore date to each user
        foreach ($ignoredUsers as $ignoredUsersID => &$ignoredUser) {
            $ignoredUser["IgnoreDate"] = $ignoredUserWithDate[$ignoredUsersID];
        }
        $ignoredUsers = array_values($ignoredUsers);
        return $ignoredUsers;
    }
}

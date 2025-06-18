<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests;

use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\Http\InternalClient;
use VanillaTests\Http\TestHttpClient;

/**
 * @method TestHttpClient api()
 */
trait UsersAndRolesApiTestTrait
{
    /** @var int|null */
    protected $lastUserID = null;

    /** @var int|null */
    protected $lastRoleID = null;

    /**
     * Run something with a specific set of permissions.
     *
     * This will create a temporary user and role with the given permissinos, and run the given callback.
     *
     * @param callable $callback The callback to run.
     * @param array $globalPermissions The global permissions to use.
     * @param array $otherPermissions Optional resource specific permissions.
     *
     * @return mixed
     */
    protected function runWithPermissions(callable $callback, array $globalPermissions, array ...$otherPermissions)
    {
        // Sign in permission is needed to start a session with the user.
        $globalPermissions = [
            "type" => "global",
            "permissions" => array_merge(
                [
                    "session.valid" => true,
                ],
                $globalPermissions
            ),
        ];

        $role = $this->createRole([
            "permissions" => array_merge([$globalPermissions], $otherPermissions),
        ]);

        $user =
            $this->createUser([
                "roleID" => [$this->lastRoleID],
            ]) ?? [];
        try {
            $result = $this->runWithUser($callback, $user);
            return $result;
        } finally {
            $id = $user["userID"] ?? $this->lastUserID;
            // Cleanup.
            $this->api()->deleteWithBody("/users/{$id}");
            $this->api()->delete("/roles/{$role["roleID"]}");
        }
    }

    /**
     * Delete all users except System and Admin(circleci).
     */
    protected function clearUsers()
    {
        $userModel = \Gdn::userModel();
        $users = $userModel->get()->resultArray();
        foreach ($users as $user) {
            if (!in_array($user["Name"], ["circleci", "System"])) {
                $userModel->deleteID($user["UserID"]);
            }
        }
    }

    /**
     * Run something with a particular API user.
     *
     * @param callable $callback The callback to run.
     * @param int|array $userOrUserID A user array or userID.
     *
     * @return mixed The result of the callback.
     */
    protected function runWithUser(callable $callback, array|int $userOrUserID)
    {
        $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
        $apiUserBefore = $this->api()->getUserID();
        $this->api()->setUserID($userID);
        \CategoryModel::clearUserCache($userID);
        try {
            Gdn::session()->ensureSession();
            $result = call_user_func($callback);
        } finally {
            $this->api()->setUserID($apiUserBefore);
            \CategoryModel::clearUserCache($apiUserBefore);
        }

        return $result;
    }

    /**
     * Helper for creating a category permission definition.
     *
     * @param int $categoryID
     * @param array $permissions
     * @return array
     */
    protected function categoryPermission(int $categoryID, array $permissions): array
    {
        return [
            "id" => $categoryID,
            "permissions" => $permissions,
            "type" => "category",
        ];
    }

    /**
     * Clear local info between tests.
     *
     * @param bool $resetRoles Whether or not roles should be wiped.
     */
    public function setUpUsersAndRolesApiTestTrait(bool $resetRoles = true): void
    {
        $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);
        $this->lastUserID = null;
        $this->lastRoleID = null;

        if ($resetRoles) {
            // Roles can be corrupted in between tests. Make sure there is a fresh start for them.
            \PermissionModel::resetAllRoles();
        }
    }

    /**
     * Create an user through the API.
     *
     * @param array $overrides
     * @param array $extras Extra fields to set directly through the model.
     * @param array $notificationPreferences
     * @param array $profileFields An array of Key -> Value to set hte profile fields.
     * @return array
     */
    protected function createUser(
        array $overrides = [],
        array $extras = [],
        array $notificationPreferences = [],
        array $profileFields = []
    ): array {
        $salt = $this->generateSalt();

        if (isset($overrides["roleID"]) && !is_array($overrides["roleID"])) {
            $overrides["roleID"] = [$overrides["roleID"]];
        }

        $body = $overrides + [
            "bypassSpam" => false,
            "email" => "test-$salt@test.com",
            "emailConfirmed" => true,
            "sendWelcomeEmail" => false,
            "name" => "user-$salt",
            "password" => "testpassword",
            "photo" => null,
            "roleID" => [\RoleModel::MEMBER_ID],
        ];

        $result = $this->api()
            ->post("/users", $body)
            ->getBody();
        $this->lastUserID = $result["userID"];

        if (!empty($extras)) {
            \Gdn::userModel()->setField($this->lastUserID, $extras);
        }

        if (!empty($notificationPreferences)) {
            $this->api()->patch("/notification-preferences/$this->lastUserID", $notificationPreferences);
        }

        if (!empty($profileFields)) {
            $this->api()->patch("/users/$this->lastUserID/profile-fields", $profileFields);
        }

        return $result;
    }

    /**
     * Create a user with the default moderator role.
     *
     * @param array $overrides
     * @param array $extras
     * @return array
     */
    protected function createGlobalMod(array $overrides = [], array $extras = []): array
    {
        $overrides = array_merge_recursive($overrides, [
            "roleID" => [\RoleModel::MOD_ID],
        ]);
        return $this->createUser($overrides, $extras);
    }

    /**
     * Create a user with a custom role that makes them the moderator of a specific category.
     *
     * @param array $categoryOrCategories
     * @param array $overrides
     * @param array $extras
     *
     * @return array
     */
    protected function createCategoryMod(array $categoryOrCategories, array $overrides = [], array $extras = []): array
    {
        return $this->createUserWithCategoryPermissions(
            $categoryOrCategories,
            [
                "discussions.view" => true,
                "discussions.add" => true,
                "comments.add" => true,
                "posts.moderate" => true,
            ],
            $overrides,
            $extras
        );
    }

    public function createUserWithCategoryPermissions(
        array $categoryOrCategories,
        array $permissionOnCategory = [
            "discussions.view" => true,
            "discussions.add" => true,
            "comments.add" => true,
            "comments.edit" => true,
        ],
        array $overrides = [],
        array $extras = []
    ) {
        if (!method_exists($this, "createCategory")) {
            TestCase::fail("Using createCategoryMod requires the CommunityApiTestTrait.");
        }

        if (isset($categoryOrCategories["categoryID"])) {
            $categoryIDs = [$categoryOrCategories["categoryID"]];
        } else {
            $categoryIDs = array_column($categoryOrCategories, "categoryID");
        }

        // The category needs custom permissions
        $categoryPermissions = [];

        foreach ($categoryIDs as $categoryID) {
            $categoryPermissions[] = [
                "type" => "category",
                "id" => $categoryID,
                "permissions" => $permissionOnCategory,
            ];
        }

        // Now let's make a role with permissions on the category.
        $role = $this->createRole(
            [],
            [
                "session.valid" => true,
            ],
            ...$categoryPermissions
        );

        $overrides = array_merge_recursive($overrides, [
            "roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]],
        ]);

        return $this->createUser($overrides, $extras);
    }

    /**
     * Create a user through the API.
     *
     * @param array $updates
     *
     * @return array
     */
    protected function updateUser(array $updates): array
    {
        $userID = $updates["userID"] ?? $this->lastUserID;

        if ($userID === null) {
            throw new \Exception("There was no userID to update");
        }

        $result = $this->api()
            ->patch("/users/$userID", $updates)
            ->getBody();
        $this->lastUserID = $result["userID"];
        return $result;
    }

    /**
     * Give points to a user.
     *
     * @param int|array $userIDOrUser A user or userID
     * @param int $points
     * @param int|array|null $categoryIDOrCategory
     */
    protected function givePoints($userIDOrUser, int $points, $categoryIDOrCategory = null)
    {
        $userID = is_array($userIDOrUser) ? $userIDOrUser["userID"] : $userIDOrUser;
        $categoryID = is_array($categoryIDOrCategory) ? $categoryIDOrCategory["categoryID"] : $categoryIDOrCategory;
        if ($categoryID !== null) {
            \UserModel::givePoints($userID, $points, [0 => "Test", "CategoryID" => $categoryID]);
        } else {
            \UserModel::givePoints($userID, $points);
        }
    }

    /**
     * Create a role.
     *
     * @param array $overrides
     * @param array $globalPermissions
     * @param array[] $otherPermissions
     *
     * @return array
     */
    public function createRole(array $overrides = [], array $globalPermissions = [], array ...$otherPermissions): array
    {
        $salt = $this->generateSalt();

        $body = $overrides + [
            "canSession" => true,
            "deletable" => true,
            "description" => "A custom role.",
            "name" => "role$salt",
            "permissions" => array_merge(
                [
                    [
                        "id" => 0,
                        "permissions" => $globalPermissions,
                        "type" => "global",
                    ],
                ],
                $otherPermissions
            ),
            "personalInfo" => true,
            "type" => "member",
        ];

        $result = $this->api()
            ->post("/roles", $body)
            ->getBody();
        $this->lastRoleID = $result["roleID"];
        return $result;
    }

    /**
     * Assert that a user record has a particular value.
     *
     * @param string $field The field name.
     * @param mixed $expected the expected value.
     * @param int|null $userID The userID. Defaults to sessioned user.
     */
    public function assertUserField(string $field, $expected, int $userID = null)
    {
        $userID = $userID ?? \Gdn::session()->UserID;
        $user = \Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        $this->assertEquals($expected, $user[$field]);
    }

    /**
     * Create a profile field through the API.
     *
     * @param array $overrides
     * @return array
     */
    protected function createProfileField(array $overrides = []): array
    {
        $salt = $this->generateSalt();

        $dataType = $overrides["dataType"] ?? "text";
        $apiNameType = str_replace("[]", "-array", $dataType);
        $body = $overrides + [
            "apiName" => "apiName_{$apiNameType}_{$salt}",
            "label" => "label_$salt",
            "description" => "description $salt",
            "dataType" => $dataType,
            "formType" => "text",
            "visibility" => "public",
            "mutability" => "all",
            "displayOptions" => [
                "profiles" => true,
                "userCards" => true,
                "posts" => true,
            ],
            "required" => false,
        ];
        $result = $this->api()->post("/profile-fields", $body);
        return $result->getBody();
    }

    /**
     * Generates and returns a salt
     *
     * @return string
     */
    private function generateSalt(): string
    {
        return VanillaTestCase::makeRandomKey("-");
    }

    /**
     * Register new user
     *
     * @param array $formFields
     */
    public function registerNewUser(array $formFields): object
    {
        return $this->runWithConfig(["Garden.Registration.Method" => "Basic"], function () use ($formFields) {
            $registrationResults = $this->bessy()->post("/entry/register", $formFields);
            $this->assertIsObject($registrationResults);
            return $registrationResults;
        });
    }

    /**
     * Get a test registration record
     *
     * @param array $overrides
     * @param array $profileFields
     * @return array
     * @throws \Exception
     */
    public static function getRegistrationRecord(array $overrides = [], array $profileFields = [])
    {
        $password = bin2Hex(random_bytes(6));
        $slug = randomString(10, "abcdefghijklmnopqrstuvwxyz1234567890");
        $record = $overrides + [
            "Email" => $slug . "@test.com",
            "Name" => $slug,
            "Password" => $password,
            "PasswordMatch" => $password,
            "TermsOfService" => "1",
            "Save" => "Save",
        ];

        if (!empty($profileFields)) {
            $record["Profile"] = $profileFields;
        }
        return $record;
    }

    /**
     * Create a user applicant record.
     *
     * @param array $overrides
     * @return array
     */
    public function createApplicant(array $overrides = []): array
    {
        $salt = substr($this->generateSalt(), 10);
        $body = $overrides + [
            "email" => "test_$salt@test.com",
            "name" => "user_$salt",
            "discoveryText" => "Hello there.",
            "password" => $salt,
        ];

        $result = $this->api()->post("/applicants", $body);
        return $result->getBody();
    }

    /**
     * Track a user visit.
     *
     * @param array|int $userOrUserID
     * @param string|null $ipAddress
     * @return void
     */
    public function trackUserVisit(array|int $userOrUserID, string|null $ipAddress = null): void
    {
        if ($ipAddress !== null) {
            \Gdn::request()->setIP($ipAddress);
        }

        $this->runWithUser(function () {
            \Gdn::userModel()->updateVisit(\Gdn::session()->UserID);
        }, $userOrUserID);
    }
}

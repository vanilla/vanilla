<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Http\InternalClient;

/**
 * @method InternalClient api()
 */
trait UsersAndRolesApiTestTrait {

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
    protected function runWithPermissions(callable $callback, array $globalPermissions, array ...$otherPermissions) {
        // Sign in permission is needed to start a session with the user.
        $globalPermissions = [
            'type' => 'global',
            'permissions' => array_merge([
                'signIn.allow' => true,
            ], $globalPermissions),
        ];

        $role = $this->createRole([
            'permissions' => array_merge([$globalPermissions], $otherPermissions),
        ]);
        $user = $this->createUser([
            'roleID' => [$this->lastRoleID],
        ]);
        $this->api()->setUserID($this->lastUserID);
        $result = call_user_func($callback);

        // Cleanup.
        $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);
        $this->api()->deleteWithBody("/users/{$user['userID']}");
        $this->api()->delete("/roles/{$role['roleID']}");
        return $result;
    }

    /**
     * Helper for creating a category permission definition.
     *
     * @param int $categoryID
     * @param array $permissions
     * @return array
     */
    protected function categoryPermission(int $categoryID, array $permissions): array {
        return [
            "id" => $categoryID,
            "permissions" => $permissions,
            "type" => "category"
        ];
    }

    /**
     * Clear local info between tests.
     *
     * @param bool $resetRoles Whether or not roles should be wiped.
     */
    public function setUpUsersAndRolesApiTestTrait(bool $resetRoles = true): void {
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
     *
     * @return array
     */
    protected function createUser(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);

        $body = $overrides + [
            'bypassSpam' => false,
            'email' => "test-$salt@test.com",
            'emailConfirmed' => true,
            'name' => "user-$salt",
            'password' => 'testpassword',
            'photo' => null,
            'roleID' => [
                \RoleModel::MEMBER_ID,
            ]
        ];

        $result = $this->api()->post('/users', $body)->getBody();
        $this->lastUserID = $result['userID'];
        return $result;
    }

    /**
     * Create an user through the API.
     *
     * @param array $updates
     *
     * @return array
     */
    protected function updateUser(array $updates): array {
        $userID = $updates['userID'] ?? $this->lastUserID;

        if ($userID === null) {
            throw new \Exception('There was no userID to update');
        }

        $result = $this->api()->patch("/users/$userID", $updates)->getBody();
        $this->lastUserID = $result['userID'];
        return $result;
    }

    /**
     * Give points to a user.
     *
     * @param int|array $userIDOrUser A user or userID
     * @param int $points
     * @param int|array|null $categoryIDOrCategory
     */
    protected function givePoints($userIDOrUser, int $points, $categoryIDOrCategory = null) {
        $userID = is_array($userIDOrUser) ? $userIDOrUser['userID'] : $userIDOrUser;
        $categoryID = is_array($categoryIDOrCategory) ? $categoryIDOrCategory['categoryID'] : $categoryIDOrCategory;
        if ($categoryID !== null) {
            \UserModel::givePoints($userID, $points, [0 => 'Test', 'CategoryID' => $categoryID]);
        } else {
            \UserModel::givePoints($userID, $points);
        }
    }

    /**
     * Create a role.
     *
     * @param array $overrides
     * @return array
     */
    public function createRole(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);

        $body = $overrides + [
            "canSession" => true,
            "deletable" => true,
            "description" => "A custom role.",
            "name" => "role$salt",
            "permissions" => [
                [
                    "id" => 0,
                    "permissions" => [],
                    "type" => "global"
                ]
            ],
            "personalInfo" => true,
            "type" => "member",
        ];

        $result = $this->api()->post('/roles', $body)->getBody();
        $this->lastRoleID = $result['roleID'];
        return $result;
    }
}

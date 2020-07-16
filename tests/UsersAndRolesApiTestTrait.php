<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests;

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
     * Clear local info between tests.
     */
    public function setUpUsersAndRolesApiTestTrait(): void {
        $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);
        $this->lastUserID = null;
        $this->lastRoleID = null;
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

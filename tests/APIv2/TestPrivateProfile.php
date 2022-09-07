<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class TestPrivateProfile
 *
 * @package VanillaTests\APIv2
 */
class TestPrivateProfile extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * @var \Gdn_Configuration
     */
    private $configuration;

    /** @var array  Private user test data. */
    protected static $userData = [];

    /**
     * Disable email before running tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->configuration = static::container()->get("Config");
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get("UsersAPIController");

        /** @var \RolesApiController $rolesAPIController */
        $rolesAPIController = static::container()->get("RolesApiController");

        $r = rand(1, 10000);
        $roleParams = [
            "name" => "role$r",
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "profiles.view" => true,
                        "session.valid" => true,
                        "personalInfo.view" => false,
                    ],
                ],
            ],
        ];

        self::$userData["userRole"] = $rolesAPIController->post($roleParams);
        $userParams = [
            "name" => "memberUser-$r",
            "password" => "testpassword",
            "email" => "test-$r@test.com",
            "roleID" => [self::$userData["userRole"]["roleID"]],
        ];
        self::$userData["userMember"] = $usersAPIController->post($userParams);
    }

    /**
     * Prepare data for testing private users.
     *
     * @param array $options
     * @return array Test data.
     */
    private function prepareData(array $options): array
    {
        return $this->runWithUser(function () use ($options) {
            $roleID = self::$userData["userRole"]["roleID"];
            $this->api()->patch("/roles/$roleID", [
                "permissions" => [
                    [
                        "type" => "global",
                        "permissions" => [
                            "personalInfo.view" => $options["personalView"],
                        ],
                    ],
                ],
            ]);
            $apiUser = $this->createUserFixture(self::$userData["userRole"]["name"]);
            $user = $this->createUser();
            if ($options["banned"]) {
                $this->api()->put("/users/{$user["userID"]}/ban", ["banned" => $options["banned"]]);
            }
            $this->userModel->saveAttribute($user["userID"], ["Private" => $options["private"] ?? false]);
            return [
                "user" => $user,
                "apiUser" => $apiUser,
            ];
        }, self::$siteInfo["adminUserID"]);
    }

    /**
     * Test APIv2 GET /Users with private & banned users.
     *
     * @param bool $banned
     * @param bool $private
     * @param bool $permPersonalView
     * @param bool $privateBannedConfig
     * @param bool $returnFullRecord
     *
     * @dataProvider provideUsersPrivateProfile
     */
    public function testUsersPrivateProfileIndex(
        bool $banned,
        bool $private,
        bool $permPersonalView,
        bool $privateBannedConfig,
        bool $returnFullRecord
    ): void {
        $options = [
            "banned" => $banned,
            "private" => $private,
            "privateBanned" => $privateBannedConfig,
            "personalView" => $permPersonalView,
        ];

        $userData = $this->prepareData($options);

        $rows = $this->runWithConfig(
            ["Vanilla.BannedUsers.PrivateProfiles" => $options["privateBanned"]],
            function () use ($userData) {
                $this->api()->setUserID($userData["apiUser"]);
                return $this->api()
                    ->get("/users")
                    ->getBody();
            }
        );
        // User record to check for private profile fields.
        $checkUser = [];
        foreach ($rows as $row) {
            if ($row["userID"] === $userData["user"]["userID"]) {
                $checkUser = $row;
            }
        }

        if (!$returnFullRecord) {
            $this->assertArrayHasKey("banned", $checkUser);
            $this->assertArrayHasKey("photoUrl", $checkUser);
            $this->assertArrayHasKey("name", $checkUser);
            $this->assertArrayNotHasKey("roles", $checkUser);
            $this->assertArrayNotHasKey("dateLastActive", $checkUser);
        } else {
            $this->assertArrayHasKey("roles", $checkUser);
        }
    }

    /**
     * Provide parameters for testing private profiles.
     */
    public function provideUsersPrivateProfile(): array
    {
        // banned, private, testRolePersonalView, privateBannedEnabled, returnFullRecord
        return [
            "member-private" => [false, true, false, false, false],
            "member-private-personalViewPermission" => [false, true, true, false, true],
            "private-banned" => [true, true, false, false, false],
            "private-banned-personalViewPermission" => [true, true, true, false, true],
            "private-banned-privateBannedEnabled" => [true, true, false, true, false],
            "private-banned-personalViewPermission-privateBannedEnabled" => [true, true, true, true, true],
            "no-changes" => [false, false, false, false, true],
            "banned" => [true, false, false, false, true],
            "banned-privateBannedEnabled" => [true, false, false, true, false],
            "banned-personalViewPermission-privateBannedEnabled" => [true, false, true, true, true],
            "private-personalViewPermission-privateBannedEnabled" => [false, true, true, true, true],
        ];
    }

    /**
     * Test UserModel::filterPrivateUserRecord/shouldIncludePrivateRecord.
     *
     * @param bool $banned
     * @param bool $private
     * @param bool $permPersonalView
     * @param bool $privateBannedConfig
     * @param bool $returnFullRecord
     * @dataProvider provideUsersPrivateProfile
     */
    public function testFilterPrivateUserRecord(
        bool $banned,
        bool $private,
        bool $permPersonalView,
        bool $privateBannedConfig,
        bool $returnFullRecord
    ): void {
        $options = [
            "banned" => $banned,
            "private" => $private,
            "privateBanned" => $privateBannedConfig,
            "personalView" => $permPersonalView,
        ];
        $userData = $this->prepareData($options);
        $fullRecord = $this->runWithUser(function () use ($userData) {
            return $this->api()
                ->get("/users/{$userData["user"]["userID"]}")
                ->getBody();
        }, self::$siteInfo["adminUserID"]);
        $this->runWithConfig(["Vanilla.BannedUsers.PrivateProfiles" => $options["privateBanned"]], function () use (
            $userData,
            $fullRecord,
            $returnFullRecord,
            $options
        ) {
            $this->api()->setUserID($userData["apiUser"]);
            $this->userModel->filterPrivateUserRecord($fullRecord);
            if (!$returnFullRecord) {
                $this->assertArrayHasKey("banned", $fullRecord);
                $this->assertArrayHasKey("photoUrl", $fullRecord);
                $this->assertArrayHasKey("name", $fullRecord);
                $this->assertArrayNotHasKey("roles", $fullRecord);
                $this->assertArrayNotHasKey("dateLastActive", $fullRecord);
                $this->assertFalse($this->userModel->shouldIncludePrivateRecord($fullRecord));
            } else {
                $this->assertArrayHasKey("roles", $fullRecord);
                $this->assertTrue($this->userModel->shouldIncludePrivateRecord($fullRecord));
            }
        });
    }

    /**
     * Test APIv2 GET /Users/{id} with private & banned users.
     *
     * @param bool $banned
     * @param bool $private
     * @param bool $permPersonalView
     * @param bool $privateBanned
     * @param bool $returnFullRecord
     *
     * @dataProvider provideUsersPrivateProfile
     */
    public function testUsersPrivateProfileGet(
        bool $banned,
        bool $private,
        bool $permPersonalView,
        bool $privateBanned,
        bool $returnFullRecord
    ) {
        $options = [
            "banned" => $banned,
            "private" => $private,
            "privateBanned" => $privateBanned,
            "personalView" => $permPersonalView,
        ];
        $userData = $this->prepareData($options);
        $row = $this->runWithConfig(
            ["Vanilla.BannedUsers.PrivateProfiles" => $options["privateBanned"]],
            function () use ($userData) {
                $this->api()->setUserID($userData["apiUser"]);
                return $this->api()
                    ->get("/users/{$userData["user"]["userID"]}")
                    ->getBody();
            }
        );

        if (!$returnFullRecord) {
            $this->assertArrayHasKey("banned", $row);
            $this->assertArrayHasKey("photoUrl", $row);
            $this->assertArrayHasKey("name", $row);
            $this->assertArrayNotHasKey("roles", $row);
            $this->assertArrayNotHasKey("dateLastActive", $row);
        } else {
            $this->assertArrayHasKey("roles", $row);
        }
    }

    /**
     * Test APIv2 GET /Users/by-names with private & banned users.
     *
     * @param bool $banned
     * @param bool $private
     * @param bool $permPersonalView
     * @param bool $privateBanned
     * @param bool $returnFullRecord
     *
     * @dataProvider provideUsersPrivateProfile
     */
    public function testUsersPrivateProfileGetByNames(
        bool $banned,
        bool $private,
        bool $permPersonalView,
        bool $privateBanned,
        bool $returnFullRecord
    ) {
        $options = [
            "banned" => $banned,
            "private" => $private,
            "privateBanned" => $privateBanned,
            "personalView" => $permPersonalView,
        ];
        $userData = $this->prepareData($options);
        $rows = $this->runWithConfig(
            ["Vanilla.BannedUsers.PrivateProfiles" => $options["privateBanned"]],
            function () use ($userData) {
                $this->api()->setUserID($userData["apiUser"]);
                return $this->api()
                    ->get("/users/by-names", ["name" => $userData["user"]["name"]])
                    ->getBody();
            }
        );
        // User record to check for private profile fields.
        $checkUser = [];
        foreach ($rows as $row) {
            if ($row["userID"] === $userData["user"]["userID"]) {
                $checkUser = $row;
            }
        }
        if (!$returnFullRecord) {
            $this->assertArrayHasKey("banned", $checkUser);
            $this->assertArrayHasKey("photoUrl", $checkUser);
            $this->assertArrayHasKey("name", $checkUser);
            $this->assertArrayNotHasKey("dateLastActive", $checkUser);
        } else {
            $this->assertArrayHasKey("dateLastActive", $checkUser);
        }
    }
}

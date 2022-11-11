<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use UserModel;
use UsersApiController;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Events\EventAction;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\PrivateCommunityMiddleware;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/users endpoints.
 */
class UsersTest extends AbstractResourceTest
{
    use TestPutFieldTrait;
    use AssertLoggingTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestSortingTrait;
    use TestFilterDirtyRecordsTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $editFields = ["email", "name"];

    /** {@inheritdoc} */
    protected $patchFields = ["name", "email", "photo", "emailConfirmed", "bypassSpam"];

    /**
     * @var \Gdn_Configuration
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/users";
        $this->resourceName = "user";
        $this->record = [
            "name" => null,
            "email" => null,
        ];
        $this->sortFields = ["dateInserted", "dateLastActive", "name", "userID"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Disable email before running tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->configuration = static::container()->get("Config");
        $this->configuration->set("Garden.Email.Disabled", true);

        /* @var PrivateCommunityMiddleware $middleware */
        $middleware = static::container()->get(PrivateCommunityMiddleware::class);
        $middleware->setIsPrivate(false);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    public function record()
    {
        $count = static::$recordCounter;
        $name = "user_{$count}";
        $record = [
            "name" => $name,
            "email" => "$name@example.com",
        ];
        static::$recordCounter++;
        return $record;
    }

    /**
     * Provide fields for registration tests.
     *
     * @param array $extra
     * @return array
     */
    private function registrationFields(array $extra = [])
    {
        static $inc = 0;

        $name = "vanilla_" . $inc++;
        $fields = [
            "email" => "{$name}@example.com",
            "name" => $name,
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "termsOfService" => 1,
        ];
        $fields = array_merge($fields, $extra);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row)
    {
        $row = parent::modifyRow($row);
        if (array_key_exists("name", $row)) {
            $row["name"] = substr(md5($row["name"]), 0, 20);
        }
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            switch ($key) {
                case "email":
                    $value = md5($value) . "@vanilla.example";
                    break;
                case "photo":
                    $hash = md5(microtime());
                    $value = "https://vanillicon.com/v2/{$hash}.svg";
                    break;
                case "emailConfirmed":
                case "bypassSpam":
                    $value = !$value;
                    break;
                case "password":
                    $value = md5(microtime());
                    break;
            }
            $row[$key] = $value;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields()
    {
        $fields = [
            "ban" => ["ban", true, "banned"],
        ];
        return $fields;
    }

    /**
     * Test Password Strength / Password Length Exception.
     *
     * @param array $fields
     * @return null|void
     */
    private function passwordException(array $fields)
    {
        if (!isset($fields["password"])) {
            return;
        }
        if (array_key_exists("name", $fields) && array_key_exists("password", $fields)) {
            $this->expectExceptionMessage("The password is too weak.");
            $this->expectException(ClientException::class);
        } else {
            if (array_key_exists("password", $fields)) {
                $this->expectExceptionMessage(
                    "Your password must be at least " . \Gdn::config("Garden.Password.MinLength") . " characters long."
                );
                $this->expectException(ClientException::class);
            }
        }
    }

    /**
     * Test removing a user's photo.
     */
    public function testDeletePhoto()
    {
        $userID = $this->testPostPhoto();

        $response = $this->api()->delete("{$this->baseUrl}/{$userID}/photo");
        $this->assertEquals(204, $response->getStatusCode());

        $user = $this->api()
            ->get("{$this->baseUrl}/{$userID}")
            ->getBody();
        $this->assertStringEndsWith(UserModel::PATH_DEFAULT_AVATAR, $user["photoUrl"]);
    }

    /**
     * Test confirm email is successful.
     */
    public function testConfirmEmailSucceed()
    {
        /** @var UserModel $userModel */
        $userModel = self::container()->get("UserModel");

        $emailKey = ["confirmationCode" => "test123"];

        $user = $this->testPost();
        $userModel->saveAttribute($user["userID"], "EmailKey", $emailKey["confirmationCode"]);

        $response = $this->api()->post("{$this->baseUrl}/{$user["userID"]}/confirm-email", $emailKey);

        $user = $userModel->getID($user["userID"]);
        $this->assertEquals(1, $user->Confirmed);
    }

    /**
     * Test confirm email fails.
     */
    public function testConfirmEmailFail()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'
        );

        /** @var UserModel $userModel */
        $userModel = self::container()->get("UserModel");

        $emailKey = ["confirmationCode" => "test123"];
        $user = $this->testPost();
        $userModel->saveAttribute($user["userID"], "EmailKey", "123Test");

        $this->api()->post("{$this->baseUrl}/{$user["userID"]}/confirm-email", $emailKey);
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null)
    {
        $row = $this->testPost();
        $result = parent::testGetEdit($row);
        return $result;
    }

    /**
     * Test getting current user info when the user is a guest.
     */
    public function testMeGuest()
    {
        $this->api()->setUserID(0);

        $response = $this->api()->get("{$this->baseUrl}/me");
        $this->assertSame(200, $response->getStatusCode());

        $header = $response->getHeader("cache-control");
        $this->assertSame(CacheControlConstantsInterface::NO_CACHE, $header);

        $expected = [
            "userID" => 0,
            "name" => "Guest",
            "photoUrl" => UserModel::getDefaultAvatarUrl(),
            "dateLastActive" => null,
            "isAdmin" => false,
            "countUnreadNotifications" => 0,
            "countUnreadConversations" => 0,
            "permissions" => ["activity.view", "discussions.view", "profiles.view"],
            "email" => null,
            "ssoID" => null,
        ];
        $actual = $response->getBody();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test getting current menu counts /users/me-counts.
     */
    public function testMeCounts()
    {
        $response = $this->api()->get("{$this->baseUrl}/me-counts");
        $this->assertSame(200, $response->getStatusCode());

        $response = $response->getBody();

        $this->assertArrayHasKey("counts", $response);
    }

    /**
     * Test getting current user info when the user is a valid member.
     */
    public function testMeMember()
    {
        /** @var UserModel $userModel */
        $userModel = self::container()->get("UserModel");
        $userID = $this->api()->getUserID();
        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);
        $dateLastActive = $user["DateLastActive"] ? date("c", strtotime($user["DateLastActive"])) : null;

        $response = $this->api()->get("{$this->baseUrl}/me");
        $this->assertSame(200, $response->getStatusCode());

        $expected = [
            "userID" => $userID,
            "name" => $user["Name"],
            "photoUrl" => userPhotoUrl($user),
            "email" => $user["Email"],
            "ssoID" => null,
            "dateLastActive" => $dateLastActive,
            "isAdmin" => true,
            "countUnreadNotifications" => 0,
            "countUnreadConversations" => 0,
            "permissions" => [
                "activity.delete",
                "activity.view",
                "advancedNotifications.allow",
                "applicants.manage",
                "comments.add",
                "comments.delete",
                "comments.edit",
                "community.manage",
                "community.moderate",
                "conversations.add",
                "curation.manage",
                "discussions.add",
                "discussions.announce",
                "discussions.close",
                "discussions.delete",
                "discussions.edit",
                "discussions.sink",
                "discussions.view",
                "email.view",
                "internalInfo.view",
                "personalInfo.view",
                "profiles.edit",
                "profiles.view",
                "session.valid",
                "settings.view",
                "site.manage",
                "uploads.add",
                "users.add",
                "users.delete",
                "users.edit",
            ],
        ];
        $actual = $response->getBody();

        $this->assertArraySubsetRecursive($expected, $actual);
    }

    /**
     * Test full-name filtering with GET /users/by-names.
     */
    public function testNamesFull()
    {
        $users = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $testUser = array_pop($users);

        $request = $this->api()->get("{$this->baseUrl}/by-names", ["name" => $testUser["name"]]);
        $this->assertEquals(200, $request->getStatusCode());
        $searchFull = $request->getBody();
        $row = reset($searchFull);
        $this->assertEquals($testUser["userID"], $row["userID"]);
    }

    /**
     * Test name search exact match.
     */
    public function testNameSearch(): void
    {
        $user1 = $this->createUser(["name" => "test1_test1"]);
        $user2 = $this->createUser(["name" => "test1_test"]);
        $result = $this->api()
            ->get("/users/by-names", ["name" => $user1["name"]])
            ->getBody();
        $this->assertEquals(1, count($result));
        $result = $this->api()
            ->get("/users/by-names", ["name" => "{$user2["name"]}*"])
            ->getBody();
        $this->assertEquals(2, count($result));
    }

    /**
     * Test partial-name filtering with GET /users/by-names.
     */
    public function testNamesWildcard()
    {
        $users = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $testUser = array_pop($users);

        $partialName = substr($testUser["name"], 0, -1);
        $request = $this->api()->get("{$this->baseUrl}/by-names", ["name" => "{$partialName}*"]);
        $this->assertEquals(200, $request->getStatusCode());
        $searchWildcard = $request->getBody();
        $this->assertNotEmpty($searchWildcard);

        $found = false;
        foreach ($searchWildcard as $user) {
            // Make sure all the required fields are included.
            $this->assertArrayHasKey("userID", $user);
            $this->assertArrayHasKey("name", $user);
            $this->assertArrayHasKey("photoUrl", $user);

            // Make sure this is a valid match.
            $this->assertStringStartsWith($partialName, $user["name"]);

            // Make sure our user is actually in the result.
            if ($testUser["userID"] == $user["userID"]) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Unable to successfully lookup user by name with wildcard.");
    }

    /**
     * Test PATCH /users/<id> with a full record overwrite.
     */
    public function testPatchFull()
    {
        $row = $this->testGetEdit();
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", $newRow);

        $this->assertEquals(200, $r->getStatusCode());

        // Setting a photo requires the "photo" field, but output schemas use "photoUrl" as a URL to the actual photo. Account for that.
        $newRow["photoUrl"] = $newRow["photo"];
        unset($newRow["photo"]);

        $this->assertRowsEqual($newRow, $r->getBody());
        $this->assertSame($r["photoUrl"], $r["profilePhotoUrl"]);
        $this->assertLog(["event" => EventAction::eventName($this->resourceName, EventAction::UPDATE)]);

        return $r->getBody();
    }

    /**
     * Test setting a user's roles with a PATCH request.
     *
     * @return array
     */
    public function testPatchWithRoles()
    {
        $roleIDs = [
            32, // Moderator
        ];
        $user = $this->testPost();
        $result = $this->api()
            ->patch("{$this->baseUrl}/{$user["userID"]}", ["roleID" => $roleIDs])
            ->getBody();

        $userRoleIDs = array_column($result["roles"], "roleID");
        if (array_diff($roleIDs, $userRoleIDs)) {
            $this->fail("Not all roles set on user.");
        }
        if (array_diff($userRoleIDs, $roleIDs)) {
            $this->fail("Unexpected roles on user.");
        }

        return $result;
    }

    /**
     * Test PATCH /users/<id> password length exeption.
     */
    public function testPatchPasswordLengthException()
    {
        $row = $this->testGetEdit();
        $patchField = ["password" => "test"];
        $this->passwordException($patchField);
        $r = $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", $patchField);
    }

    /**
     * {@inheritdoc}
     */
    public function testPost($record = null, array $extra = [])
    {
        $record = $this->record();
        $fields = [
            "bypassSpam" => true,
            "emailConfirmed" => false,
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $result = parent::testPost($record, $fields);
        return $result;
    }

    /**
     * Test post password exception.
     */
    public function testPostPasswordException()
    {
        $fields =
            [
                "bypassSpam" => true,
                "emailConfirmed" => false,
                "password" => "test",
            ] + $this->record();
        $this->passwordException($fields);
        parent::testPost($fields);
    }

    /**
     * Test adding a photo for a user.
     *
     * @return int ID of the user used for this test.
     */
    public function testPostPhoto()
    {
        $user = $this->testGet();

        TestUploader::resetUploads();
        $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");
        $response = $this->api()->post("{$this->baseUrl}/{$user["userID"]}/photo", ["photo" => $photo]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertIsArray($response->getBody());

        $responseBody = $response->getBody();
        $this->assertArrayHasKey("photoUrl", $responseBody);
        $this->assertNotEmpty($responseBody["photoUrl"]);
        $this->assertNotFalse(filter_var($responseBody["photoUrl"], FILTER_VALIDATE_URL), "Photo is not a valid URL.");
        $this->assertStringEndsNotWith(
            UserModel::PATH_DEFAULT_AVATAR,
            $responseBody["photoUrl"],
            "The response returned the default avatar URL."
        );
        $this->assertNotEquals($user["photoUrl"], $responseBody["photoUrl"]);

        $this->assertUploadedFileUrlExists($responseBody["photoUrl"]);

        $user = $this->api()
            ->get("{$this->baseUrl}/{$user["userID"]}")
            ->getBody();
        $this->assertUploadedFileUrlExists($user["photoUrl"]);
        $this->assertUploadedFileUrlExists($user["profilePhotoUrl"]);
        $this->assertNotEquals($user["photoUrl"], $user["profilePhotoUrl"]);

        return $user["userID"];
    }

    /**
     * Test adding a new user with non-default roles.
     */
    public function testPostWithRoles()
    {
        $roleIDs = [
            32, // Moderator
        ];
        $record = $this->record();
        $fields = [
            "bypassSpam" => true,
            "emailConfirmed" => false,
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "roleID" => $roleIDs,
        ];
        $result = parent::testPost($record, $fields);

        $userRoleIDs = array_column($result["roles"], "roleID");
        if (array_diff($roleIDs, $userRoleIDs)) {
            $this->fail("Not all roles set on user.");
        }
        if (array_diff($userRoleIDs, $roleIDs)) {
            $this->fail("Unexpected roles on user.");
        }

        return $result;
    }

    /**
     * Basic registration.
     */
    public function testRegisterBasic()
    {
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get("Config");
        $configuration->set("Garden.Registration.Method", "Basic");
        $configuration->set("Garden.Registration.ConfirmEmail", false);
        $configuration->set("Garden.Registration.SkipCaptcha", true);
        $configuration->set("Garden.Email.Disabled", true);

        $fields = $this->registrationFields();
        $this->verifyRegistration($fields);
    }

    /**
     * Basic registration to password exception test.
     */
    public function testRegisterBasicPasswordException()
    {
        $fields = $this->registrationFields();
        $fields["password"] = "test";
        $this->passwordException($fields);
        $this->api()->post("/users/register", $fields);
    }

    /**
     * I should be able to invoke basic registration on a private community.
     */
    public function testRegisterBasicPrivateCommunity()
    {
        $this->runWithPrivateCommunity([$this, "testRegisterBasic"]);
    }

    /**
     * Register with an invitation code.
     */
    public function testRegisterInvitation()
    {
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get("Config");
        $configuration->set("Garden.Registration.Method", "Invitation");
        $configuration->set("Garden.Registration.ConfirmEmail", false);
        $configuration->set("Garden.Registration.SkipCaptcha", true);
        $configuration->set("Garden.Email.Disabled", true);

        $fields = $this->registrationFields();
        $invitation = $this->runWithAdminUser(function () use ($fields) {
            return $this->api()
                ->post("/invites", ["email" => $fields["email"]])
                ->getBody();
        });
        $fields["invitationCode"] = $invitation["code"];
        $this->verifyRegistration($fields);
    }

    /**
     * Users should be able to register an invitation with private community turned on.
     */
    public function testRegisterInvitationPrivateCommunity()
    {
        $this->runWithPrivateCommunity([$this, "testRegisterInvitation"]);
    }

    /**
     * Test the full request of a lost password workflow.
     */
    public function testRequestPassword()
    {
        static $i = 1;

        // Create a user first.
        $user = $this->runWithAdminUser(function () use (&$i) {
            $r = $this->api()
                ->post("/users", [
                    "name" => "testRequestPassword" . $i,
                    "email" => "userstest$i@example.com",
                    "password" => "123Test234Test",
                ])
                ->getBody();

            $i++;

            return $r;
        });
        $r = $this->api()->post("/users/request-password", ["email" => $user["email"]]);

        $this->assertLog(["event" => "password_reset_skipped", "data.email" => $user["email"]]);

        try {
            $this->runWithConfig(
                [
                    "Garden.Registration.NameUnique" => true,
                    "Garden.Registration.EmailUnique" => true,
                ],
                function () use ($user) {
                    $this->getTestLogger()->clear();
                    $r = $this->api()->post("/users/request-password", ["email" => $user["name"]]);
                }
            );
            $this->fail('You shouldn\'t be able to reset a password with a username.');
        } catch (ClientException $ex) {
            $this->assertEquals(400, $ex->getCode());
        }

        $this->runWithConfig(
            [
                "Garden.Registration.NameUnique" => true,
                "Garden.Registration.EmailUnique" => false,
            ],
            function () use ($user) {
                $this->getTestLogger()->clear();
                $r = $this->api()->post("/users/request-password", ["email" => $user["name"]]);
                $this->assertLog(["event" => "password_reset_skipped", "data.email" => $user["email"]]);
            }
        );
    }

    /**
     * Users should be able to request their passwords with private community on.
     */
    public function testRequestPasswordPrivateCommunity()
    {
        $this->runWithPrivateCommunity([$this, "testRequestPassword"]);
    }

    /**
     * A moderator should be able to ban a member.
     */
    public function testBanWithPermission()
    {
        $this->createUserFixtures("testBanWithPermission");
        $this->api()->setUserID($this->moderatorID);
        $r = $this->api()->put("{$this->baseUrl}/{$this->memberID}/ban", ["banned" => true]);
        $this->assertTrue($r["banned"]);

        // Make sure the user has the banned photo.
        $user = $this->api()
            ->get("{$this->baseUrl}/{$this->memberID}")
            ->getBody();
        $this->assertStringEndsWith(UserModel::PATH_BANNED_AVATAR, $user["photoUrl"]);
        $this->assertSame($user["photoUrl"], $user["profilePhotoUrl"]);
    }

    /**
     * A moderator should not be able to ban an administrator.
     */
    public function testBanWithoutPermission()
    {
        $this->createUserFixtures("testBanWithoutPermission");
        $this->api()->setUserID($this->moderatorID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user that has higher permissions than you.");
        $r = $this->api()->put("/users/{$this->adminID}/ban", ["banned" => true]);
    }

    /**
     * A moderator should not be able to ban another moderator.
     */
    public function testBanSamePermissionRank()
    {
        $this->createUserFixtures("testBanSamePermissionRank");
        $moderatorID = $this->moderatorID;
        $this->createUserFixtures("testBanSamePermissionRank2");
        $this->api()->setUserID($this->moderatorID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user with the same permission level as you.");
        $r = $this->api()->put("/users/{$moderatorID}/ban", ["banned" => true]);
    }

    /**
     * Perform a registration and verify the result.
     *
     * @param array $fields
     */
    private function verifyRegistration(array $fields)
    {
        $registration = $this->api()
            ->post("/users/register", $fields)
            ->getBody();
        $user = $this->runWithAdminUser(function () use ($registration) {
            return $this->api()
                ->get("/users/{$registration[$this->pk]}")
                ->getBody();
        });
        $registeredUser = array_intersect_key($registration, $user);
        ksort($registration);
        ksort($registeredUser);
        $this->assertEquals($registration, $registeredUser);
    }

    /**
     * Test the users role filter.
     */
    public function testRoleFilter(): void
    {
        $roleID = $this->getRoles()["Moderator"];

        $users = $this->api()
            ->get("/users", ["roleID" => $roleID])
            ->getBody();
        $this->assertNotEmpty($users);
        foreach ($users as $user) {
            $this->assertTrue(
                in_array($roleID, array_column($user["roles"], "roleID")),
                "The user does not satisfy the roleID filter."
            );
        }
    }

    /**
     * Test GET /:ID with a member role
     */
    public function testGetUserViewProfileOnly()
    {
        $user = $this->testPost();
        $user2 = $this->testPost();

        /** @var UserModel $userModel */
        $userModel = static::container()->get(UserModel::class);
        $userModel->setField($user2["userID"], "ShowEmail", 1);

        $this->api()->setUserID($user["userID"]);

        $response = $this->api()
            ->get("/users/{$user2["userID"]}")
            ->getBody();

        /** @var UsersApiController $userApiController */
        $userApiController = static::container()->get(UsersApiController::class);
        $viewProfileSchema = $userApiController->viewProfileSchema();
        $viewProfileSchema->validate($response);

        $this->assertArrayHasKey("name", $response);
        $this->assertArrayHasKey("email", $response);
        $this->assertArrayHasKey("photoUrl", $response);
        $this->assertArrayHasKey("dateInserted", $response);
        $this->assertArrayHasKey("dateLastActive", $response);
        $this->assertArrayHasKey("countDiscussions", $response);
        $this->assertArrayHasKey("countComments", $response);
    }

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    protected function triggerDirtyRecords()
    {
        $this->resetTable("dirtyRecord");
        $user = $this->createUser();
        $this->givePoints($user["userID"], 10);
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array
    {
        return [
            "resourceType" => "user",
            "primaryKey" => "userID",
        ];
    }

    /**
     * Test GET /:ID user with personal info role.
     */
    public function testGetPersonalInfoProfile(): void
    {
        $role = $this->createRole([
            "name" => "New Role",
            "personalInfo" => true,
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "session.valid" => true,
                    ],
                ],
            ],
        ]);
        // Create a user with personalInfo set to true.
        $userA = $this->createUser(["name" => "userA", "roleID" => [$role["roleID"]]]);
        // a user without personalInfo.View permission should not be able to view role info.
        $userB = $this->createUser(["name" => "userB"]);
        $this->runWithUser(function () use ($userA) {
            $result = $this->api()
                ->get("/users/{$userA["userID"]}")
                ->getBody();
            $this->assertArrayNotHasKey("roles", $result);
        }, $userB);
        // As an admin, role info should be visible.
        $result = $this->api()
            ->get("/users/{$userA["userID"]}")
            ->getBody();
        $this->assertArrayHasKey("roles", $result);
    }
    /**
     * Primarily used for obtaining a role token for tests that utilize a role token via the **depends** annotation
     *
     * @return array
     */
    public function testGetRoleTokenQueryParam()
    {
        $tokenResponseBody = $this->getRoleTokenResponseBody();
        $this->assertArrayHasKey("roleToken", $tokenResponseBody);
        return [static::getRoleTokenParamName() => $tokenResponseBody["roleToken"]];
    }

    /**
     * Test that the get users/{id} endpoint accepts role token auth
     *
     * @param array $roleTokenQueryParam
     * @depends testGetRoleTokenQueryParam
     */
    public function testIndexWithRoleTokenAuth(array $roleTokenQueryParam)
    {
        $user = $this->testPost();

        $this->api()->setUserID(0);
        $response = $this->api()
            ->get("/users/{$user["userID"]}", $roleTokenQueryParam)
            ->getBody();

        /** @var UsersApiController $userApiController */
        $userApiController = static::container()->get(UsersApiController::class);
        $viewProfileSchema = $userApiController->viewProfileSchema();

        $this->assertArrayHasKey("name", $response);
        $this->assertArrayHasKey("email", $response);
        $this->assertArrayHasKey("photoUrl", $response);
        $this->assertArrayHasKey("dateInserted", $response);
        $this->assertArrayHasKey("dateLastActive", $response);
        $this->assertArrayHasKey("countDiscussions", $response);
        $this->assertArrayHasKey("countComments", $response);

        $this->assertSame($user["name"], $response["name"]);
        $this->assertSame($user["email"], $response["email"]);
    }

    /**
     * Provider for testPatchUserProfileFieldsWithMutability
     *
     * @return array[]
     */
    public function providePatchUserProfileFieldsWithMutabilityData(): array
    {
        return [
            "test with mutability all" => ["all", false, false],
            "test with mutability restricted" => ["restricted", true, false],
            "test with mutability none" => ["none", true, true],
        ];
    }

    /**
     * Tests permissions for PATCH /users/{id}/profile-fields endpoint depending on profile field's mutability
     *
     * @param string $mutability
     * @param bool $failsForSameUser
     * @param bool $failsForUserWithPermission
     * @return void
     * @dataProvider providePatchUserProfileFieldsWithMutabilityData
     */
    public function testPatchUserProfileFieldsWithMutability(
        string $mutability,
        bool $failsForSameUser,
        bool $failsForUserWithPermission
    ) {
        $user = $this->createUser();
        $profileField = $this->createProfileField(["mutability" => $mutability]);

        $test = function () use ($user, $profileField) {
            return $this->api()->patch("$this->baseUrl/{$user["userID"]}/profile-fields", [
                $profileField["apiName"] => "123",
            ]);
        };

        // Test when user updates their own fields
        $this->runWithUser(function () use ($failsForSameUser, $test) {
            if ($failsForSameUser) {
                $this->runWithExpectedExceptionCode(403, $test);
            } else {
                $result = $test();
                $this->assertSame(200, $result->getStatusCode());
            }
        }, $user);

        // Test with Garden.Users.Edit permission
        $this->runWithPermissions(
            function () use ($failsForUserWithPermission, $test) {
                if ($failsForUserWithPermission) {
                    $this->runWithExpectedExceptionCode(403, $test);
                } else {
                    $result = $test();
                    $this->assertSame(200, $result->getStatusCode());
                }
            },
            ["users.edit" => true]
        );

        // Endpoint should always fail when called as guest
        $this->runWithUser(function () use ($test) {
            $this->runWithExpectedExceptionCode(403, $test);
        }, UserModel::GUEST_USER_ID);
        \Gdn::userModel()->deleteID($user["userID"]);
    }

    /**
     * Provider for testPatchAndGetUserProfileFields
     *
     * @return array[]
     */
    public function providePatchAndGetUserProfileFieldsData(): array
    {
        return [
            ["text", "text", [], "abc", "abc"],
            ["text", "text", [], 123, "123"],
            ["text", "text", [], true, null, true],
            ["boolean", "checkbox", [], true, true],
            ["boolean", "checkbox", [], "abc", null, true],
            ["date", "date", [], "abc", null, true],
            ["date", "date", [], "2022-09-01T05:54:26.990Z", "2022-09-01T05:54:26+00:00"],
            ["number", "number", [], 42, 42],
            ["number", "number", [], "42", 42],
            ["number", "number", [], "abc", null, true],
            ["string[]", "tokens", ["abc", "123"], "abc", null, true],
            ["string[]", "tokens", ["one", "2", "3"], ["one", 2, 3], ["one", "2", "3"]],
            ["number[]", "tokens", [1, 2, 3], "abc", null, true],
            ["number[]", "tokens", [7, 8, 9], ["7", 8, 9], [7, 8, 9]],
        ];
    }

    /**
     * @param string $dataType
     * @param string $formType
     * @param mixed $patchValue
     * @param mixed $expectedResponseValue
     * @param bool $expectsException
     * @return void
     * @dataProvider providePatchAndGetUserProfileFieldsData
     */
    public function testPatchAndGetUserProfileFields(
        string $dataType,
        string $formType,
        ?array $dropdownOptions,
        $patchValue,
        $expectedResponseValue,
        bool $expectsException = false
    ) {
        $user = $this->createUser();
        $profileField = $this->createProfileField([
            "dataType" => $dataType,
            "formType" => $formType,
            "dropdownOptions" => $dropdownOptions,
        ]);
        $apiName = $profileField["apiName"];

        try {
            $this->runWithUser(function () use (
                $user,
                $apiName,
                $patchValue,
                $expectedResponseValue,
                $expectsException
            ) {
                $exceptionThrown = false;
                try {
                    $requestBody = [$apiName => $patchValue];
                    $response = $this->api()->patch("$this->baseUrl/{$user["userID"]}/profile-fields", $requestBody);
                    $responseBody = $response->getBody();
                    $this->assertSame([$apiName => $expectedResponseValue], $responseBody);

                    $response = $this->api()->get("$this->baseUrl/{$user["userID"]}/profile-fields");
                    $responseBody = $response->getBody();
                    $this->assertSame([$apiName => $expectedResponseValue], $responseBody);
                } catch (\Exception $e) {
                    if (!$expectsException) {
                        throw $e;
                    }
                    $exceptionThrown = true;
                }
                $this->assertSame($expectsException, $exceptionThrown);
            },
            $user);
        } finally {
            \Gdn::userModel()->deleteID($user["userID"]);
            $userMetaModel = $this->container()->get(\UserMetaModel::class);
            $userMetaModel->delete(["UserID" => $user["userID"]]);
        }
    }

    /**
     * Provider for testGetUserProfileFieldsWithVisibility
     *
     * @return array[]
     */
    public function provideGetUserProfileFieldsWithVisibilityData(): array
    {
        return [
            "test with visibility public" => ["public", ["profiles.view" => true, "personalInfo.view" => false]],
            "test with visibility private" => ["private", ["profiles.view" => false, "personalInfo.view" => true]],
            "test with visibility internal" => [
                "internal",
                ["profiles.view" => false, "personalInfo.view" => false, "internalInfo.view" => true],
                false,
            ],
        ];
    }

    /**
     * @param string $visibility
     * @param array $permissions
     * @return void
     * @dataProvider provideGetUserProfileFieldsWithVisibilityData
     */
    public function testGetUserProfileFieldsWithVisibility(
        string $visibility,
        array $permissions,
        bool $visibleToUser = true
    ) {
        $user = $this->createUser();
        $profileField = $this->createProfileField(["visibility" => $visibility]);

        $this->api()->patch("$this->baseUrl/{$user["userID"]}/profile-fields", [
            $profileField["apiName"] => "123",
        ]);
        $test = function () use ($user) {
            return $this->api()->get("$this->baseUrl/{$user["userID"]}/profile-fields");
        };

        // Test when user views their own fields
        $this->runWithUser(function () use ($test, $visibleToUser) {
            $result = $test();
            if ($visibleToUser) {
                $this->assertNotEmpty($result->getBody());
            } else {
                $this->assertEmpty($result->getBody());
            }
        }, $user);

        // Test viewing fields with various permissions
        foreach ($permissions as $permission => $visible) {
            $this->runWithPermissions(
                function () use ($test, $permission, $visible) {
                    $result = $test();
                    if ($visible) {
                        $this->assertNotEmpty($result->getBody(), "Should be able to view with $permission");
                    } else {
                        $this->assertEmpty($result->getBody(), "Should not be able to view with $permission");
                    }
                },
                [$permission => true]
            );
        }
        \Gdn::userModel()->deleteID($user["userID"]);
    }

    /**
     * Basic tests for filtering the user list by profile field values.
     *
     * @return void
     */
    public function testIndexWithProfileFieldFilter()
    {
        self::enableFeature("ImprovedUserProfileFields");

        $text = $this->createProfileField([
            "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
            "formType" => "text",
        ])["apiName"];
        $number = $this->createProfileField([
            "dataType" => ProfileFieldModel::DATA_TYPE_NUMBER,
            "formType" => "number",
        ])["apiName"];
        $boolean = $this->createProfileField([
            "dataType" => ProfileFieldModel::DATA_TYPE_BOOL,
            "formType" => "checkbox",
        ])["apiName"];
        $date = $this->createProfileField([
            "dataType" => ProfileFieldModel::DATA_TYPE_DATE,
            "formType" => "date",
        ])["apiName"];
        $multipleStrings = $this->createProfileField([
            "dataType" => ProfileFieldModel::DATA_TYPE_STRING_MUL,
            "formType" => "dropdown",
            "dropdownOptions" => ["a", "b", "c", "d"],
        ])["apiName"];
        $private = $this->createProfileField(["visibility" => "private"])["apiName"];

        // Create `user1` & set a bunch of `profile-fields` values.
        $user1 = $this->createUser();
        $this->api()->patch("/users/{$user1["userID"]}/profile-fields", [
            $text => "a",
            $number => 10,
            $boolean => false,
            $date => "2022-10-10",
            $multipleStrings => ["a", "b", "c"],
        ]);

        // Create `user2` & set a bunch of `profile-fields` values.
        $user2 = $this->createUser();
        $this->api()->patch("/users/{$user2["userID"]}/profile-fields", [
            $text => "b",
            $number => 10,
            $boolean => true,
            $date => "2022-10-10",
            $multipleStrings => ["b", "d"],
        ]);

        // Create `user3` & set a bunch of `profile-fields` values.
        $user3 = $this->createUser();
        $this->api()->patch("/users/{$user3["userID"]}/profile-fields", [
            $text => "c",
            $number => 20,
            $boolean => true,
            $date => "2022-10-08",
            $private => "private",
        ]);

        // Test by `$multipleStrings` = a,b.
        $rows = $this->api()
            ->get("/users?sort=userID&extended[$multipleStrings]=a,b")
            ->getBody();
        $this->assertCount(2, $rows);
        $this->assertSame($user1["userID"], $rows[0]["userID"]);
        $this->assertSame($user2["userID"], $rows[1]["userID"]);

        // Test by `$text` = a,c.
        $rows = $this->api()
            ->get("/users?sort=userID&extended[$text]=a,c")
            ->getBody();
        $this->assertCount(2, $rows);
        $this->assertSame($user1["userID"], $rows[0]["userID"]);
        $this->assertSame($user3["userID"], $rows[1]["userID"]);

        // Test by `$text` = a,c & `number` = 20.
        $rows = $this->api()
            ->get("/users?sort=userID&extended[$text]=a,c&extended[$number]=10")
            ->getBody();
        $this->assertCount(1, $rows);
        $this->assertSame($user1["userID"], $rows[0]["userID"]);

        // Test by `boolean` = true.
        $rows = $this->api()
            ->get("/users?sort=userID&extended[$boolean]=true")
            ->getBody();
        $this->assertCount(2, $rows);
        $this->assertSame($user2["userID"], $rows[0]["userID"]);
        $this->assertSame($user3["userID"], $rows[1]["userID"]);

        // Test by `date` = 2022-10-10.
        $rows = $this->api()
            ->get("/users?sort=userID&extended[$date]=2022-10-10")
            ->getBody();
        $this->assertCount(2, $rows);
        $this->assertSame($user1["userID"], $rows[0]["userID"]);
        $this->assertSame($user2["userID"], $rows[1]["userID"]);

        $testcaseProvider = function ($count, $field, $value) {
            return function () use ($count, $field, $value) {
                $rows = $this->api()
                    ->get("/users?sort=userID&extended[$field]=$value")
                    ->getBody();
                $this->assertCount($count, $rows);
            };
        };

        // Users cannot filter by private profile fields of another user
        $this->runWithExpectedException(ForbiddenException::class, function () use (
            $testcaseProvider,
            $private,
            $user1
        ) {
            $this->runWithUser($testcaseProvider(0, $private, "private"), $user1["userID"]);
        });

        // They cannot filter by private profile fields even for their own account.
        $this->runWithExpectedException(ForbiddenException::class, function () use (
            $testcaseProvider,
            $private,
            $user3
        ) {
            $this->runWithUser($testcaseProvider(1, $private, "private"), $user3["userID"]);
        });

        // Users with permissions can filter by private profile fields for any user
        $testcaseProvider(1, $private, "private")();

        \Gdn::userModel()->deleteID($user1["userID"]);
        \Gdn::userModel()->deleteID($user2["userID"]);
        \Gdn::userModel()->deleteID($user3["userID"]);

        self::disableFeature("ImprovedUserProfileFields");
    }

    /**
     * Tests that the `/users` endpoint returns the correct expand fields when expand=all
     *
     * @return void
     */
    public function testIndexWithExpands()
    {
        $testCaseProvider = function (array $hasKeys = [], array $notHasKeys = []) {
            return function () use ($hasKeys, $notHasKeys) {
                $response = $this->api()
                    ->get("/users", ["expand" => "all", "sort" => "-userID"])
                    ->getBody();

                $firstResult = array_shift($response);
                foreach ($hasKeys as $key) {
                    $this->assertArrayHasKey($key, $firstResult);
                }
                foreach ($notHasKeys as $key) {
                    $this->assertArrayNotHasKey($key, $firstResult);
                }
            };
        };

        $inviter = $this->createUser();
        $invitee = $this->createUser([], ["InviteUserID" => $inviter["userID"]]);
        $this->runWithUser(
            $testCaseProvider(["countVisits", "inviteUserID", "inviteUser"], ["lastIPAddress"]),
            $this->memberID
        );
        $this->runWithAdminUser($testCaseProvider(["countVisits", "lastIPAddress", "inviteUserID", "inviteUser"]));
        \Gdn::userModel()->deleteID($inviter["userID"]);
        \Gdn::userModel()->deleteID($invitee["userID"]);
    }
}

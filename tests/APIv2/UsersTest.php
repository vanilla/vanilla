<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Gdn;
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
    use TestSortingTrait {
        testIndexSort as parentTestIndexSort;
    }
    use TestFilterDirtyRecordsTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $editFields = ["email", "name"];

    /** @var int By the time we get to the index test we actually have more than 100 items. */
    protected int $indexLimit = 200;

    /** {@inheritdoc} */
    protected $patchFields = ["name", "email", "showEmail", "photo", "emailConfirmed", "bypassSpam"];

    /** @var \Gdn_Configuration */
    const SELF_EDIT_FIELDS = ["name", "email", "showEmail", "private", "password"];

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
        $this->resetTable("profileField");
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
                case "private":
                case "showEmail":
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
     * Overridden to execute before other custom tests that generate users that don't play well with this test.
     *
     * @inheritDoc
     * @dataProvider provideSortFields
     */
    public function testIndexSort(string $field): void
    {
        $this->parentTestIndexSort($field);
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
     * Test getting current user info when the user is an administrator.
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
                "profile.editusernames",
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
     * Test that if the current user doesn't have permission to assign a role, a permission exception will be thrown.
     *
     * @return void
     */
    public function testPatchWithPermissionRestrictedRoles()
    {
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You don't have permission to assign these roles: Moderator");
        $user = $this->testPost();
        $this->runWithPermissions(
            function () use ($user) {
                $roleIDs = [\RoleModel::MEMBER_ID, \RoleModel::MOD_ID];
                $this->api()->patch("{$this->baseUrl}/{$user["userID"]}", [
                    "roleID" => $roleIDs,
                ]);
            },
            ["users.edit" => true]
        );
    }

    /**
     * Test that if the current user doesn't have permission to assign a role, but target user already has the role,
     * no permission exception will be thrown.
     *
     * @return void
     */
    public function testPatchWithPermissionRestrictedRolesButTargetUserHasRole()
    {
        $user = parent::testPost($this->record(), [
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "roleID" => [\RoleModel::MOD_ID],
        ]);
        $this->runWithPermissions(
            function () use ($user) {
                $roleIDs = [\RoleModel::MEMBER_ID, \RoleModel::MOD_ID];
                $response = $this->api()->patch("{$this->baseUrl}/{$user["userID"]}", [
                    "roleID" => $roleIDs,
                ]);
                $body = $response->getBody();
                $userRoleIDs = array_column($body["roles"], "roleID");
                $this->assertEqualsCanonicalizing($roleIDs, $userRoleIDs);
            },
            ["users.edit" => true, "community.moderate" => true]
        );
    }

    /**
     * Test PATCH /users/<id> password length exception.
     */
    public function testPatchPasswordLengthException()
    {
        $row = $this->testGetEdit();
        $patchField = ["password" => "test"];
        $this->passwordException($patchField);
        $r = $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", $patchField);
    }

    /**
     * Test that we can patch the hash method to reset.
     */
    public function testPatchEmailReset()
    {
        $existingUser = $this->createUser([
            "name" => "emailResetUser",
        ]);
        $response = $this->api()->patch("/users/{$existingUser["userID"]}", ["resetPassword" => true]);
        $this->assertEquals("Reset", $response->getBody()["hashMethod"]);
        // User's password should be reset.
        $fullUser = $this->userModel->getID($existingUser["userID"], DATASET_TYPE_ARRAY);
        // Password reset email is sent.
        $this->assertEmailSentTo($existingUser["email"]);
        // User should have password reset info.
        $passwordResetKey = $fullUser["Attributes"]["PasswordResetKey"];
        $this->assertNotNull($passwordResetKey);

        // Reset it again.
        $response = $this->api()->patch("/users/{$existingUser["userID"]}", ["resetPassword" => true]);
        $this->assertEquals("Reset", $response->getBody()["hashMethod"]);
        $fullUser = $this->userModel->getID($existingUser["userID"], DATASET_TYPE_ARRAY);

        // User should have password reset info.
        $newPasswordResetKey = $fullUser["Attributes"]["PasswordResetKey"];
        $this->assertNotNull($newPasswordResetKey);

        // And it should be different.
        $this->assertNotEquals($passwordResetKey, $newPasswordResetKey);
    }

    /**
     * Test updating a user with profile fields
     */
    public function testPatchWithProfileFields()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        $field = $this->createProfileField();
        $this->api()->patch("/users/{$user["userID"]}", ["profileFields" => [$field["apiName"] => "test"]]);

        $response = $this->api()->get("$this->baseUrl/{$user["userID"]}/profile-fields");
        $body = $response->getBody();
        $this->assertArrayHasKey($field["apiName"], $body);
        $this->assertSame("test", $body[$field["apiName"]]);
    }

    /**
     * Test that the private field can be patched.
     */
    public function testPatchPrivateField()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        $response = $this->api()->patch("/users/{$user["userID"]}", ["private" => true]);
        $this->assertTrue($response->getBody()["private"]);
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
     * Test that if the current user doesn't have permission to assign a role, a permission exception will be thrown.
     *
     * @return void
     */
    public function testPostWithPermissionRestrictedRoles()
    {
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You don't have permission to assign these roles: Moderator");

        $this->runWithPermissions(
            function () {
                $roleIDs = [\RoleModel::MEMBER_ID, \RoleModel::MOD_ID];
                parent::testPost($this->record(), [
                    "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                    "roleID" => $roleIDs,
                ]);
            },
            ["users.add" => true]
        );
    }

    /**
     * Test adding a user with profile fields
     */
    public function testPostWithProfileFields()
    {
        $field = $this->createProfileField(["name" => __FUNCTION__]);
        $body = [
            "name" => __FUNCTION__,
            "email" => uniqid() . "@email.com",
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "profileFields" => [$field["apiName"] => "test"],
        ];
        $result = $this->api()->post($this->baseUrl, $body);
        $response = $this->api()->get("$this->baseUrl/{$result["userID"]}/profile-fields");
        $body = $response->getBody();
        $this->assertArrayHasKey($field["apiName"], $body);
        $this->assertSame("test", $body[$field["apiName"]]);
    }

    /**
     * Test that users are properly created private.
     */
    public function testPostPrivate()
    {
        $body = [
            "name" => __FUNCTION__,
            "email" => uniqid() . "@email.com",
            "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "private" => true,
        ];
        $result = $this->api()
            ->post($this->baseUrl, $body)
            ->getBody();
        $this->assertTrue($result["private"]);
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
     * Test registering with `Title`, `Location` & `Gender` user meta values.
     */
    public function testRegisterBuiltInFieldsIntoUserMeta()
    {
        $this->configuration->saveToConfig(ProfileFieldModel::CONFIG_FEATURE_FLAG, true);
        $this->bessy()->get("/utility/update");
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get("Config");
        $configuration->set("Garden.Registration.Method", "Basic");
        $configuration->set("Garden.Registration.ConfirmEmail", false);
        $configuration->set("Garden.Registration.SkipCaptcha", true);
        $configuration->set("Garden.Email.Disabled", true);

        $userMeta = [
            "Title" => "This user's custom title",
            "Location" => "Where that user is hangin'",
            "Gender" => "m",
        ];
        $fields = $this->registrationFields($userMeta);
        $userID = $this->verifyRegistration($fields);

        // UserModel's `getID()` function should return both correct `Title` & `Location` values.
        $userModelData = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($userMeta, $userModelData);
    }

    /**
     * Test that pulling a user's recordset through `UserModel` `getId()` function moves `Title`, `Location` & `Gender`
     * from the `User` table to the `UserMeta` table.
     */
    public function testGetUserMovesBuiltInFieldsIntoUserMeta()
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => true], function () {
            $this->createUserFixtures("testSaveUserMoves");
            $memberData = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);

            // Member shouldn't have any fields set by default.
            $this->assertEmpty($memberData["Title"]);
            $this->assertEmpty($memberData["Location"]);
            $this->assertEmpty($memberData["DateOfBirth"]);

            $userTable = Gdn::database()->DatabasePrefix . "User";
            $newTitle = "SQL Title";
            $newLocation = "SQL Location";
            $newGender = "f";
            $newDateOfBirth = "1990-08-25 00:00:00";

            // Directly update the user's fields.
            $sql = "UPDATE $userTable SET Title = '$newTitle', Location = '$newLocation', Gender = '$newGender', DateOfBirth = '$newDateOfBirth' WHERE UserID = $this->memberID";
            Gdn::database()
                ->structure()
                ->executeQuery($sql);

            // We flush any cached data.
            self::$testCache->flush();
            // We grab a fresh recordset from the UserModel. (This will move `Title`, `Location` & `Gender` values to `UserMeta`).
            $memberData = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
            $this->assertEquals($newTitle, $memberData["Title"]);
            $this->assertEquals($newLocation, $memberData["Location"]);
            $this->assertEquals($newGender, $memberData["Gender"]);
            $this->assertEquals($newDateOfBirth, $memberData["DateOfBirth"]);

            // We grab a fresh recordset from the `User` table. The corresponding `Title`, `Location`, `Gender` & DateOfBirth values should be empty.
            $model = new \Gdn_Model("User");
            $userRecordset = $model->getID($this->memberID, DATASET_TYPE_ARRAY);
            $this->assertEmpty($userRecordset["Title"]);
            $this->assertEmpty($userRecordset["Location"]);
            $this->assertEmpty($userRecordset["Gender"]);
            $this->assertEmpty($userRecordset["DateOfBirth"]);
        });
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
     * A moderator should be able to ban a member through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanWithPermission()
    {
        $this->createUserFixtures("testPutBanWithPermission");
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
     * A user with the right permission should be able to ban a user with lower permissions through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanWithPermission(): void
    {
        $this->createUserFixtures("testPatchBanWithPermission");
        $this->api()->setUserID($this->adminID);
        $r = $this->api()->patch("{$this->baseUrl}/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // Make sure the user has the banned photo.
        $user = $this->api()
            ->get("{$this->baseUrl}/{$this->memberID}")
            ->getBody();
        $this->assertStringEndsWith(UserModel::PATH_BANNED_AVATAR, $user["photoUrl"]);
        $this->assertSame($user["photoUrl"], $user["profilePhotoUrl"]);
    }

    /**
     * A moderator should not be able to ban an administrator through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanWithoutPermission()
    {
        $this->createUserFixtures("testPutBanWithoutPermission");
        $this->api()->setUserID($this->moderatorID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user that has higher permissions than you.");
        $r = $this->api()->put("/users/{$this->adminID}/ban", ["banned" => true]);
    }

    /**
     * A user with the "users.edit" permission should not be allowed to ban an admin through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanWithoutPermission(): void
    {
        $this->createUserFixtures("testPatchBanWithoutPermission");
        $this->runWithPermissions(
            function () {
                $this->expectException(ForbiddenException::class);
                $this->expectExceptionMessage(UsersApiController::ERROR_PATCH_HIGHER_PERMISSION_USER);
                $r = $this->api()->patch("/users/{$this->adminID}", ["banned" => true]);
            },
            ["users.edit" => true]
        );
    }

    /**
     * A moderator should not be able to ban another moderator through the PUT /users/{id}/ban endpoint.
     */
    public function testPutBanSamePermissionRank()
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
     * A user should not be able to ban another user with identical permissions through the PATCH /users/{id} endpoint.
     */
    public function testPatchBanSamePermissionRank(): void
    {
        $usersEditRole = $this->createRole([], ["session.valid" => true, "users.edit" => true]);
        $user1ID = $this->createUserFixture($usersEditRole["name"]);
        $user2ID = $this->createUserFixture($usersEditRole["name"]);
        $this->api()->setUserID($user1ID);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You are not allowed to ban a user with the same permission level as you.");
        $r = $this->api()->patch("/users/{$user2ID}", ["banned" => true]);
    }

    /**
     * Test that we can patch a ban with the same value that already exists.
     */
    public function testPatchBanSameBanValue()
    {
        $this->createUserFixtures(__FUNCTION__);
        $this->api()->setUserID($this->adminID);
        $r = $this->api()->patch("{$this->baseUrl}/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // And we can do it again but nothing changes.
        $r = $this->api()->patch("{$this->baseUrl}/{$this->memberID}", ["banned" => true]);
        $this->assertSame(1, $r["banned"]);

        // And we can do the inverse.
        $r = $this->api()->patch("{$this->baseUrl}/{$this->memberID}", ["banned" => false]);
        $this->assertSame(0, $r["banned"]);

        // And again no change.
        $r = $this->api()->patch("{$this->baseUrl}/{$this->memberID}", ["banned" => false]);
        $this->assertSame(0, $r["banned"]);
    }

    /**
     * Perform a registration and verify the result.
     *
     * @param array $fields
     */
    private function verifyRegistration(array $fields): int
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

        return $registration[$this->pk];
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

        $this->runWithUser(function () use ($user, $apiName, $patchValue, $expectedResponseValue, $expectsException) {
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
        }, $user);
    }

    /**
     * Test that updating the user's profile data clears the cache and returns the updated values
     */
    public function testPatchUserProfileFieldClearsCache()
    {
        $this->configuration->saveToConfig(ProfileFieldModel::CONFIG_FEATURE_FLAG, true);
        $this->bessy()->get("/utility/update");
        $user = $this->createUser(["name" => "PatchUser"]);
        $userProfileData = [];
        $profileData = array_slice($this->providePatchAndGetUserProfileFieldsData(), 0, 2);

        foreach ($profileData as $pData) {
            $profileField = $this->createProfileField([
                "dataType" => $pData[0],
                "formType" => $pData[1],
                "dropdownOptions" => $pData[2],
            ]);
            $userProfileData[$profileField["apiName"]] = $pData[3];
        }
        //include User Meta fields if they exist and are visible
        $profileFieldModel = $this->container()->get(ProfileFieldModel::class);
        $where = [
            "apiName" => UserModel::USERMETA_FIELDS,
            "visibility" => "public",
        ];
        $userMetaFields = $profileFieldModel->getProfileFields($where);
        $userMetaValues = array_combine(UserModel::USERMETA_FIELDS, ["Test", "Portland", "1980-12-12", "u"]);
        foreach ($userMetaFields as $userMeta) {
            $apiName = $userMeta["apiName"];
            $userProfileData[$apiName] = $userMetaValues[$apiName];
            //if not enabled by default. Enable it for tests
            if (!$userMeta["enabled"]) {
                $profileFieldModel->update(["enabled" => 1], ["apiName" => $apiName]);
            }
        }

        try {
            $this->runWithUser(function () use ($user, $profileData, $userProfileData, $userMetaFields) {
                //check to see if the user cache is set initially
                $userCachedData = $this->userModel->getUserFromCache($user["userID"], "userid");
                $this->assertIsArray($userCachedData);
                //check that the values are currently empty for user profile fields
                foreach (UserModel::USERMETA_FIELDS as $fields) {
                    $this->assertEmpty($userCachedData[$fields]);
                }

                $response = $this->api()->patch("$this->baseUrl/{$user["userID"]}/profile-fields", $userProfileData);
                $responseBody = $response->getBody();
                $this->assertEquals($userProfileData, $responseBody);

                //make sure that cache is cleared after the update
                $userCachedData = $this->userModel->getUserFromCache($user["userID"], "userid");
                $this->assertFalse($userCachedData, "User cache not cleared");

                $userMetaValues = array_combine(UserModel::USERMETA_FIELDS, ["Tester", "Quebec", "1980-01-01", "f"]);

                //Now we have to update the data again and see if we get updated content or previous content
                foreach ($userProfileData as $key => $val) {
                    if (in_array($key, UserModel::USERMETA_FIELDS)) {
                        $userProfileData[$key] = $userMetaValues[$key];
                        continue;
                    }
                    $userProfileData[$key] = "updated-$val";
                }
                $response = $this->api()->patch("$this->baseUrl/{$user["userID"]}/profile-fields", $userProfileData);
                $responseBody = $response->getBody();
                $this->assertEquals($userProfileData, $responseBody);
                if (!empty($userMetaFields)) {
                    $updatedUser = $this->userModel->getID($user["userID"], true);
                    foreach ($userMetaFields as $userMeta) {
                        $this->assertEquals($userProfileData[$userMeta["apiName"]], $updatedUser[$userMeta["apiName"]]);
                    }
                }
            }, $user);
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
    }

    /**
     * Basic tests for filtering the user list by profile field values.
     *
     * @return void
     */
    public function testIndexWithProfileFieldFilter()
    {
        self::enableFeature(ProfileFieldModel::FEATURE_FLAG);

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

        self::disableFeature(ProfileFieldModel::FEATURE_FLAG);
    }

    /**
     * Test that only the last record is fetch when multiple values exists in GDN_UserMeta but that the profileField doesn't support multi-values.
     *
     * @return void
     */
    public function testGetUserProfileWithDuplicatedValue()
    {
        /** @var \UserMetaModel $userMeta */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $this->createProfileField([
            "apiName" => "Title",
            "label" => "Title",
            "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
            "formType" => "text",
        ])["apiName"];

        // Insert two records.
        $user = $this->createUser();
        $userMetaModel->insert([
            "UserID" => $user["userID"],
            "Name" => "Profile.Title",
            "Value" => __FUNCTION__,
            "QueryValue" => "Profile.Title." . __FUNCTION__,
        ]);

        $userMetaModel->insert([
            "UserID" => $user["userID"],
            "Name" => "Profile.Title",
            "Value" => __FUNCTION__ . "2",
            "QueryValue" => "Profile.Title." . __FUNCTION__ . "2",
        ]);
        // Flush the cache to make sure our duplicated records are used.
        self::$testCache->flush();

        $body = $this->api()
            ->get("/users/{$user["userID"]}/profile-fields")
            ->getBody();

        $this->assertEquals(__FUNCTION__ . "2", $body["Title"]);

        $result = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
        $this->assertEquals(__FUNCTION__ . "2", $result["Title"]);
    }

    /**
     * Test that empty required fields do not trigger an error when fetching.
     *
     * @return void
     */
    public function testGetProfileFieldWithEmptyFields()
    {
        /** @var \UserMetaModel $userMeta */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);

        $user = $this->createUser();
        $profileField = $this->createProfileField([
            "registrationOptions" => "required",
        ])["apiName"];

        $userMetaModel->insert([
            "UserID" => $user["userID"],
            "Name" => "Profile.$profileField",
            "Value" => "",
            "QueryValue" => $profileField . __FUNCTION__ . "2",
        ]);

        $body = $this->api()
            ->get("/users/{$user["userID"]}/profile-fields")
            ->getBody();

        $this->assertEquals("", $body[$profileField]);
    }

    /**
     * Tests that we can expand invite users.
     *
     * @return void
     */
    public function testInviteUserData()
    {
        $inviter = $this->createUser(["name" => "Inviter"]);
        $invitee = $this->createUser(["name" => "Invitee"], ["InviteUserID" => $inviter["userID"]]);
        $fetchedInvitee = $this->api()
            ->get("/users/{$invitee["userID"]}", ["expand" => "inviteUser"])
            ->getBody();
        $this->assertEquals($inviter["userID"], $fetchedInvitee["inviteUserID"]);
        $this->assertEquals($inviter["name"], $fetchedInvitee["inviteUser"]["name"]);
    }

    /**
     * Test that only admin users get IP addresses in user responses.
     */
    public function testIpAddressIsAdminOnly()
    {
        $this->createUserFixtures();
        $memberResult = $this->runWithUser(function () {
            return $this->api()
                ->get("/users/{$this->memberID}")
                ->getBody();
        }, $this->memberID);
        $this->assertArrayNotHasKey("lastIPAddress", $memberResult);

        $adminResult = $this->runWithAdminUser(function () {
            return $this->api()
                ->get("/users/{$this->memberID}")
                ->getBody();
        });
        $this->assertArrayHasKey("lastIPAddress", $adminResult);
    }

    /**
     * Test that members can self-edit their profile.
     */
    public function testPatchSelfEditMemberSuccess()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "password" => "password1234",
        ]);
        $body = [
            "email" => uniqid() . "@email.com",
            "password" => "my-new-amazing-password-that-no-one-will-ever-guess",
            "passwordConfirmation" => "password1234",
            "showEmail" => false,
            "private" => true,
        ];

        $this->runWithUser(function () use ($user, $body) {
            $response = $this->api()->patch("/users/{$user["userID"]}", $body);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotFalse($this->userModel->validateCredentials("", $user["userID"], $body["password"]));

            unset($body["password"]);
            unset($body["passwordConfirmation"]);
            $this->assertDataLike($body, $response->getBody());
        }, $user);
    }

    /**
     * Test that admin can self-edit their profiles.
     */
    public function testPatchSelfEditAdminSuccess()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "password" => "password1234",
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);

        $body = [
            "name" => uniqid(),
            "email" => uniqid() . "@email.com",
            "password" => "my-new-amazing-password-that-no-one-will-ever-guess",
            "passwordConfirmation" => "password1234",
            "showEmail" => false,
            "private" => true,
            "bypassSpam" => true,
        ];

        $this->runWithUser(function () use ($user, $body) {
            $response = $this->api()->patch("/users/{$user["userID"]}", $body);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotFalse($this->userModel->validateCredentials("", $user["userID"], $body["password"]));

            unset($body["password"]);
            unset($body["passwordConfirmation"]);
            $this->assertDataLike($body, $response->getBody());
        }, $user);
    }

    /**
     * Test that members are not allowed to edit other profile.
     */
    public function testPatchSelfEditWrongUserID()
    {
        $user1 = $this->createUser(["name" => __FUNCTION__ . 1]);
        $user2 = $this->createUser([
            "name" => __FUNCTION__ . 2,
            "password" => "password1234",
        ]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () use ($user2) {
            $this->api()->patch("/users/{$user2["userID"]}", [
                "name" => uniqid(),
                "passwordConfirmation" => "password1234",
            ]);
        }, $user1);
    }

    /**
     * Test that the user has the "Garden.Profiles.Edit" permission to edit their profile.
     */
    public function testPatchSelfEditPermissionError()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "password" => "password1234",
            "roleID" => [\RoleModel::GUEST_ID],
        ]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () use ($user) {
            $this->api()->patch("/users/{$user["userID"]}", [
                "name" => uniqid(),
                "email" => "no@email.com",
                "password" => __FUNCTION__ . "42",
                "passwordConfirmation" => "password1234",
                "showEmail" => true,
                "private" => true,
            ]);
        }, $user);
    }

    /**
     * Test that an error is thrown when self-editing without `passwordConfirmation`.
     */
    public function testPatchSelfEditWrongPasswordError()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "roleID" => [\RoleModel::ADMIN_ID],
            "password" => "password1234",
        ]);
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("The password you entered is incorrect.");
        $this->runWithUser(function () use ($user) {
            $this->api()->patch("/users/{$user["userID"]}", [
                "name" => uniqid(),
                "passwordConfirmation" => __FUNCTION__,
            ]);
        }, $user);
    }

    /**
     * Test that password confirmation is only required if a value changes.
     */
    public function testPatchSelfOnlyRequiresPasswordConfirmationOnChange()
    {
        $user = $this->createUser([
            "name" => "patchSelfEditSameData",
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);
        // I can patch the same email and username without needing confirmation.
        $r = $this->api()->patch("/users/{$user["userID"]}", [
            "name" => $user["name"],
            "email" => $user["email"],
        ]);
        $this->assertEquals(200, $r->getStatusCode());
    }

    /**
     * Test that an error is thrown when self-editing the username without `passwordConfirmation`.
     */
    public function testPatchSelfEditNamePasswordConfirmationMissingError()
    {
        $user = $this->createUser([
            "name" => "patchSelfEditConfirmMissing",
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(UsersApiController::ERROR_SELF_EDIT_PASSWORD_MISSING);
        $this->runWithUser(function () use ($user) {
            $this->api()->patch("/users/{$user["userID"]}", [
                "name" => $user["name"] . "-1",
            ]);
        }, $user);
    }

    /**
     * Test that an error is thrown when self-editing the email without `passwordConfirmation`.
     */
    public function testPatchSelfEditEmailPasswordConfirmationMissingError()
    {
        $user = $this->createUser([
            "name" => "EditEmailPasswordConfirmationMissingError",
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(UsersApiController::ERROR_SELF_EDIT_PASSWORD_MISSING);
        $this->runWithUser(function () use ($user) {
            $this->api()->patch("/users/{$user["userID"]}", [
                "email" => uniqid() . "@email.com",
            ]);
        }, $user);
    }

    /**
     * Test that an error is thrown when self-editing the password without `passwordConfirmation`.
     */
    public function testPatchSelfEditCredentialsPasswordConfirmationMissingError()
    {
        $user = $this->createUser([
            "name" => "EditCredentialsPasswordConfirmationMissingError",
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(UsersApiController::ERROR_SELF_EDIT_PASSWORD_MISSING);
        $this->runWithUser(function () use ($user) {
            $this->api()->patch("/users/{$user["userID"]}", [
                "password" => uniqid(),
            ]);
        }, $user);
    }

    /**
     * Test that users with a hashMethod set to `Random` bypass the password check since they don't have any.
     */
    public function testPatchSelfEditRandomHash()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        $this->userModel->setField($user["userID"], ["HashMethod" => "Random"]);

        $this->runWithUser(function () use ($user) {
            $name = uniqid();
            $response = $this->api()->patch("/users/{$user["userID"]}", [
                "name" => $name,
            ]);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($name, $response->getBody()["name"]);
        }, $user);
    }

    /**
     * Test that members users are not allowed to self-edit fields unauthorized fields.
     */
    public function testPatchSelfEditInvalidField()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "password" => "password1234",
        ]);

        $body = $this->modifyRow($user + ["photo" => ""]);
        $body["passwordConfirmation"] = "password1234";
        // remove valid fields for self-edit
        foreach (self::SELF_EDIT_FIELDS as $field) {
            unset($body[$field]);
        }

        $this->runWithUser(function () use ($user, $body) {
            $response = $this->api()->patch("/users/{$user["userID"]}", $body);
            $this->assertEquals(200, $response->getStatusCode());

            unset($user["dateUpdated"]);
            unset($user["lastIPAddress"]); // User doesn't have access to this because they are not an admin.
            $this->assertDataLike($user, $response->getBody());
        }, $user);
    }

    /**
     * Test that users do not need to input a password for a field that does not require it when doing a self-edit.
     */
    public function testPatchSelfEditNoPasswordRequired()
    {
        $user = $this->createUser(["name" => __FUNCTION__, "password" => "password1234"]);
        $field = $this->createProfileField(["name" => __FUNCTION__]);
        $body = [
            "profileFields" => [$field["apiName"] => "test"],
        ];
        $this->api()->patch("users/{$user["userID"]}", $body);
        $result = $this->api()
            ->get("$this->baseUrl/{$user["userID"]}/profile-fields")
            ->getBody();
        $this->assertArrayHasKey($field["apiName"], $result);
        $this->assertSame("test", $result[$field["apiName"]]);
    }

    /**
     * Test that self-editing has rate limiting.
     */
    public function testPatchSelfEditRateLimitingError()
    {
        $this->expectException(\Garden\Web\Exception\ServerException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("You are trying to log in too often. Slow down!.");

        $this->runWithConfig(["Garden.User.RateLimit" => 1], function () {
            $user = $this->createUser([
                "name" => uniqid(),
                "password" => __FUNCTION__,
            ]);

            $this->runWithUser(function () use ($user) {
                for ($i = 0; $i < 10; $i++) {
                    try {
                        $this->api()->patch("/users/{$user["userID"]}", [
                            "name" => __FUNCTION__,
                            "passwordConfirmation" => __FUNCTION__ . $i,
                        ]);
                    } catch (\Garden\Web\Exception\ClientException $e) {
                        // Wrong password.
                    }
                }
            }, $user);
        });
    }

    /**
     * Test that a user with the `site.manage` permission can edit another user with the `site.manage` permission.
     */
    public function testPatchPrivilegeUserSuccess()
    {
        $admin1 = $this->createUser([
            "name" => __FUNCTION__ . 1,
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);
        $admin2 = $this->createUser([
            "name" => __FUNCTION__ . 2,
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);

        $permissions = $this->userModel->getPermissions($admin2["userID"]);
        $this->assertTrue($permissions->has("site.manage"));

        $this->runWithUser(function () use ($admin2) {
            $name = uniqid();
            $response = $this->api()->patch("/users/{$admin2["userID"]}", [
                "name" => $name,
            ]);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($name, $response->getBody()["name"]);
        }, $admin1);
    }

    /**
     * Test that a user with only the users.edit permission can not can a user with the `site.manage` permission.
     */
    public function testPatchPrivilegeUserFail()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "roleID" => [\RoleModel::ADMIN_ID],
        ]);

        $permissions = $this->userModel->getPermissions($user["userID"]);
        $this->assertTrue($permissions->has("site.manage"));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(UsersApiController::ERROR_PATCH_HIGHER_PERMISSION_USER);
        $this->runWithPermissions(
            function () use ($user) {
                $this->api()->patch("/users/{$user["userID"]}", [
                    "roleID" => [\RoleModel::ADMIN_ID],
                ]);
            },
            ["users.edit" => true]
        );
    }

    /**
     * Test that a user with the `site.manage` permission can edit roles.
     */
    public function testPatchRoleSuccess()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);

        $this->runWithPermissions(
            function () use ($user) {
                $response = $this->api()->patch("/users/{$user["userID"]}", [
                    "roleID" => [\RoleModel::ADMIN_ID],
                ]);
                $this->assertEquals(200, $response->getStatusCode());
                $this->assertUserHasRoles($user["userID"], [\RoleModel::ADMIN_ID]);
            },
            ["site.manage" => true, "users.edit" => true]
        );
    }

    /**
     * Test that a user with only the users.edit permission can not edit roles.
     */
    public function testPatchRoleFail()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You don't have permission to assign these roles: Administrator");
        $this->runWithPermissions(
            function () use ($user) {
                $this->api()->patch("/users/{$user["userID"]}", [
                    "roleID" => [\RoleModel::ADMIN_ID],
                ]);
            },
            ["users.edit" => true]
        );
    }

    /**
     * Test user changing it's own username with and without the Garden.Username.Edit permission.
     */
    public function testPermissionsChangeUserName()
    {
        $password = randomString(\Gdn::config("Garden.Password.MinLength"));
        $user = $this->createUser(["name" => __FUNCTION__, "password" => $password]);

        $requestBody = [
            "name" => "IWantANewUsername",
            "password" => $password,
            "passwordConfirmation" => $password,
        ];

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start($user["userID"]);

        // Enabling the Garden.Username.Edit permission, modify the current user's userName.
        $session->setPermission("Garden.Username.Edit", true);
        $response = $this->api()->patch("/users/{$user["userID"]}", $requestBody);
        $responseBody = $response->getBody();
        // Assert that the request processed correctly & the username is changed.
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($requestBody["name"], $responseBody["name"]);

        // Disabling the Garden.Username.Edit permission, try  to modify the current user's userName.
        $requestBody["name"] = "IWantANewUsernameAgain";
        $session->setPermission("Garden.Username.Edit", false);
        // We are expecting this won't work & a "Forbidden" exception will be thrown.
        $this->expectExceptionMessage("Permission Problem");
        $this->api()->patch("/users/{$user["userID"]}", $requestBody);
    }
}

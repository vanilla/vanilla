<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use League\Uri\Http;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Utility\ModelUtils;
use VanillaTests\AuditLogTestTrait;
use VanillaTests\Bootstrap;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use Garden\EventManager;
use Vanilla\Dashboard\Events\UserEvent;
use \UserModel;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;
use ActivityModel;

/**
 * Test {@link UserModel}.
 */
class UserModelTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait {
        createUser as protected createUserByApi;
    }
    use AuditLogTestTrait;

    public static $addons = ["vanilla", "dashboard", "conversations"];

    /** @var UserEvent */
    private $lastEvent;

    /**
     * @var int
     */
    private $ssoRoleID1;

    /**
     * @var int
     */
    private $ssoRoleID2;

    /** @var ActivityModel */
    private $activityModel;

    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var \CommentModel */
    private $commentModel;

    /** @var \ConversationModel */
    private $conversationModel;

    /** @var \ConversationMessageModel */
    private $conversationMessageModel;

    /** @var \SessionModel */
    private $sessionModel;

    private ProfileFieldModel $profileFieldModel;

    /** @var \Gdn_Configuration */
    private $config;

    /**
     * @inheritDoc
     */
    public function setup(): void
    {
        parent::setUp();

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;

        $this->container()->call(function (EventManager $eventManager, \Gdn_Configuration $config) {
            $eventManager->addListenerMethod(self::class, "handleUserEvent");
            $this->config = $config;
        });

        $this->config->set(
            [
                // Relax username validation.
                "Garden.User.ValidationRegexPattern" => '`^[a-zA-Z0-9_ ]*$`',
            ],
            null
        );
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        $this->commentModel = $this->container()->get(\CommentModel::class);
        $this->conversationModel = $this->container()->get(\ConversationModel::class);
        $this->conversationMessageModel = $this->container()->get(\ConversationMessageModel::class);
        $this->sessionModel = $this->container()->get(\SessionModel::class);
        $this->profileFieldModel = $this->container()->get(ProfileFieldModel::class);

        // Add a couple of test SSO roles.
        $this->ssoRoleID1 = $this->defineRole(["Name" => "SSO 1", "Sync" => "sso"]);
        $this->ssoRoleID2 = $this->defineRole(["Name" => "SSO 2", "Sync" => "sso"]);

        $this->createUserFixtures();
    }

    /**
     * A test listener that increments the counter.
     *
     * @param UserEvent $e
     * @return UserEvent
     */
    public function handleUserEvent(UserEvent $e): UserEvent
    {
        $this->lastEvent = $e;

        return $e;
    }

    /**
     * Adds crypted "password" to the allowed banTypes of the "Ban Rules" dashboard interface.
     *
     * @param array $banTypes
     * @return array
     */
    public function settingsController_listBanTypes(array $banTypes): array
    {
        $banTypes["Password"] = t("Password");
        return $banTypes;
    }

    /**
     * Add ordering users by Email in the dashboard's users list.
     *
     * @param array $allowedSorting
     * @return array
     */
    public function userController_usersListAllowedSorting(array $allowedSorting): array
    {
        $allowedSorting["Password"] = "desc";
        return $allowedSorting;
    }

    /**
     * Add crypted password string to the dashboard's users list search query.
     *
     * @param array $whereCriterias
     * @param string $keywords
     * @return array
     */
    public function userModel_searchKeyWords_handler(array $whereCriterias, string $keywords): array
    {
        $whereCriterias["where"]["u.Password"] = $keywords;

        return $whereCriterias;
    }

    /**
     * Add uncrypted "Password" to the possible ban query.
     *
     * @param array $result
     * @param array $ban
     * @param string $prepend
     * @return array
     */
    public function banModel_banWhere_handler(
        array $result,
        array $ban,
        string $prepend = "",
        bool $inverse = false
    ): array {
        $inverse = $inverse ? " !" : "";
        switch (strtolower($ban["BanType"])) {
            case "password":
                $result["{$prepend}Password{$inverse}"] = $ban["BanValue"];
                break;
        }
        return $result;
    }

    /**
     * Either add "Password" row header or a crypted password to the dashboard's list of users.
     *
     * @param \Gdn_Controller $sender
     * @param array $args
     */
    public function base_userCell_handler($sender, $args)
    {
        // If we have user data, we create a cell containing the crypted password.
        if (isset($args["User"])) {
            echo "<td>" . $args["User"]->Password ?? "" . "</td>";
        } else {
            echo '<th class="column-md">' . t("Password") . "</th>";
        }
    }

    /**
     * Create a dummy user for testing.
     *
     * @param array $overrides
     * @return array
     */
    protected function dummyUser(array $overrides = []): array
    {
        $user = self::sprintfCounter(
            array_replace(
                [
                    "Name" => "user%s",
                    "Email" => "user%s@example.com",
                    "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                ],
                $overrides
            )
        );

        return $user;
    }

    /**
     * Make sure the setup fixtures work.
     */
    public function testSetUp()
    {
        $this->assertIsInt($this->ssoRoleID1);
        $this->assertIsInt($this->ssoRoleID2);
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void
    {
        $user = [
            "Name" => "testuser",
            "Email" => "testuser@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];

        $userID = $this->userModel->save($user);
        $this->userModel->deleteID($userID);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Test incrementLoginAttempt method.
     *
     * @return void
     */
    public function testIncrementLoginAttempt(): void
    {
        $user = [
            "Name" => "testuser2",
            "Email" => "testuser2@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userID = $this->userModel->save($user);
        $this->userModel->incrementLoginAttempt($userID);
        $this->userModel->incrementLoginAttempt($userID);
        $loggingAttempts = $this->userModel->getAttribute($userID, "LoggingAttempts");
        $dateLastFailedLogin = $this->userModel->getAttribute($userID, "DateLastFailedLogin");
        $this->assertEquals(2, $loggingAttempts);
        $this->assertNotNull($dateLastFailedLogin);
    }

    /**
     * Test isSuspendedAndResetBasedOnTime method.
     *
     * @return void
     */
    public function testIsSuspendedAndResetBasedOnTime(): void
    {
        $this->config->set(["Garden.SignIn.Attempts" => 3, "Garden.SignIn.LockoutTime" => 600], null);
        $user = [
            "Name" => "testuser5",
            "Email" => "testuser5@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userID = $this->userModel->save($user);
        $this->userModel->incrementLoginAttempt($userID);
        $this->userModel->incrementLoginAttempt($userID);
        $suspended = $this->userModel->isSuspendedAndResetBasedOnTime($userID);
        // Not suspended yet.
        $this->assertEquals(false, $suspended);
        // Suspended now.
        $this->userModel->incrementLoginAttempt($userID);
        $suspended = $this->userModel->isSuspendedAndResetBasedOnTime($userID);
        $this->assertEquals(true, $suspended);
        $this->userModel->saveToSerializedColumn("Attributes", $userID, [
            "DateLastFailedLogin" => DateTimeFormatter::timeStampToDateTime(strtotime("now") - 700),
        ]);
        $suspended = $this->userModel->isSuspendedAndResetBasedOnTime($userID);
        // Enough time passed, time to clear suspension.
        $this->assertEquals(false, $suspended);
        $loggingAttempts = $this->userModel->getAttribute($userID, "LoggingAttempts");
        $dateLastFailedLogin = $this->userModel->getAttribute($userID, "DateLastFailedLogin", null);
        $this->assertEquals(0, $loggingAttempts);
        $this->assertNull($dateLastFailedLogin);
    }

    /**
     * Test suspension error message with various time intervals.
     *
     * @param int $lockoutTime Number of seconds lockout is set.
     * @param string $expectedMessage - expected response message.
     *
     * @dataProvider lockoutTimes
     */
    public function testSuspendedErrorMessage(int $lockoutTime, string $expectedMessage): void
    {
        $this->config->set("Garden.SignIn.LockoutTime", $lockoutTime, true);
        $this->config->set("Garden.SignIn.Attempts", 1, true);
        $errorMessage = $this->userModel->suspendedErrorMessage();
        $this->assertSame($errorMessage, $expectedMessage);
    }

    /**
     * Provide data for the suspension error message.
     */
    public function lockoutTimes(): array
    {
        $defaultErrorMessage = "You’ve reached the maximum login attempts. Please wait %s and try again.";
        return [
            "less then 1 second" => [1, sprintf($defaultErrorMessage, "1 second")],
            "less then 60 seconds" => [45, sprintf($defaultErrorMessage, "45 seconds")],
            "exactly then 60 seconds" => [60, sprintf($defaultErrorMessage, "1 minute")],
            "exactly then 90 seconds" => [90, sprintf($defaultErrorMessage, "2 minutes")],
            "exactly then 120 seconds" => [120, sprintf($defaultErrorMessage, "2 minutes")],
            "exactly then 3600 seconds" => [3600, sprintf($defaultErrorMessage, "1 hour")],
            "exactly then 7200 seconds" => [7200, sprintf($defaultErrorMessage, "2 hours")],
            "exactly then 7261 seconds" => [7261, sprintf($defaultErrorMessage, "2 hours")],
        ];
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void
    {
        $user = [
            "Name" => "testuser2",
            "Email" => "testuser2@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $this->userModel->save($user);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_INSERT, $this->lastEvent->getAction());
    }

    /**
     * Verify update event dispatched during save.
     *
     * @return void
     */
    public function testSaveUpdateEventDispatched(): void
    {
        $user = [
            "Name" => "testuser3",
            "Email" => "testuser3@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userUpdate = [
            "Name" => "testuser3",
            "Email" => "testuser4@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userID = $this->userModel->save($user);
        $user = (array) $this->userModel->getID($userID);
        $userUpdate["UserID"] = $user["UserID"];
        $this->userModel->save($userUpdate);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }

    /**
     * Test searching for users by a role keyword.
     */
    public function testSearchByRole(): void
    {
        $roles = $this->getRoles();
        $adminRole = $roles["Administrator"];

        // Make sure we have at least one non-admin.
        $this->userModel->save([
            "Name" => __FUNCTION__,
            "Email" => __FUNCTION__ . "@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "RoleID" => $roles["Member"],
        ]);

        $users = $this->userModel->search("Administrator")->resultArray();
        \RoleModel::setUserRoles($users);

        $result = true;
        foreach ($users as $user) {
            $userRoles = array_keys($user["Roles"]);
            if (!in_array($adminRole, $userRoles)) {
                $result = false;
                break;
            }
        }

        $this->assertTrue($result, "Failed to only return users in the specific role.");
    }

    /**
     * Test UserModel::search().
     *
     * @param string $username
     * @param string $keywords
     * @param int $expectedCount
     * @dataProvider searchDataProvider
     */
    public function testSearch(string $username, string $keywords, int $expectedCount): void
    {
        if ($username) {
            $this->createUser($username);
        }
        $result = $this->userModel->search(["Keywords" => $keywords]);
        $this->assertEquals($expectedCount, $result->numRows());
    }

    /**
     * Test cases data provider
     */
    public function searchDataProvider(): array
    {
        return [
            "regular search" => ["searcha", "searcha", 1],
            "regular search 2" => ["abcsearcha", "searcha", 1],
            "search user with space" => ["search a", "search a", 1],
            "user with underscore" => ["search_a", "search_a", 1],
            "wildcard search both" => ["", "%searcha%", 2],
            "wildcard search left" => ["", "%searcha", 2],
            "wildcard search right" => ["", "searcha%", 1],
            "wildcard both username with space" => ["", "%search a%", 1],
            "wildcard left username with space" => ["", "%search a", 1],
            "wildcard right username with space" => ["", "search a%", 1],
            "wildcard right multiple users" => ["", "search%", 3],
        ];
    }

    /**
     * Create  User.
     *
     * @param string $userName
     * @return int|bool
     */
    private function createUser(string $userName)
    {
        $rand = randomString(25);
        $user = [
            "Name" => $userName,
            "Email" => $rand . "test@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];

        $id = $this->userModel->save($user);
        ModelUtils::validationResultToValidationException($this->userModel);
        return $id;
    }

    /**
     * I should be able to save an absolute list of role IDs.
     */
    public function testSaveRoles()
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);
        $roleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertSame(
            [$this->roleID(Bootstrap::ROLE_MEMBER)],
            $roleIDs,
            'The test user doesn\'t have the right roles.'
        );

        $setRoleIDs = [$this->roleID(Bootstrap::ROLE_ADMIN)];
        $this->userModel->saveRoles($userID, $setRoleIDs, [\UserModel::OPT_LOG_ROLE_CHANGES => true]);
        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertSame($setRoleIDs, $newRoleIDs);

        $this->assertLog([
            "event" => "user_roleModification",
            "data.rolesAdded.0" => Bootstrap::ROLE_ADMIN,
            "data.rolesRemoved.0" => Bootstrap::ROLE_MEMBER,
        ]);
    }

    /**
     * I should be able to add roles.
     */
    public function testAddRoles(): int
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);

        $this->userModel->addRoles($userID, [$this->roleID(Bootstrap::ROLE_ADMIN)], true);
        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertEqualsCanonicalizing(
            [$this->roleID(Bootstrap::ROLE_MEMBER), $this->roleID(Bootstrap::ROLE_ADMIN)],
            $newRoleIDs
        );

        $this->assertLog(["event" => "user_roleModification", "data.rolesAdded.0" => Bootstrap::ROLE_ADMIN]);

        return $userID;
    }

    /**
     * I should be able to remove roles from a user.
     *
     * @param int $userID
     * @depends testAddRoles
     */
    public function testRemoveRoles(int $userID): void
    {
        $this->userModel->removeRoles($userID, [$this->roleID(Bootstrap::ROLE_MEMBER)], true);

        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertEqualsCanonicalizing([$this->roleID(Bootstrap::ROLE_ADMIN)], $newRoleIDs);
        $this->assertLog(["event" => "user_roleModification", "data.rolesRemoved.0" => Bootstrap::ROLE_MEMBER]);
    }

    /**
     * Test UserModel->getInvitationCount
     */
    public function testGetInvitationCount(): void
    {
        \Gdn::config()->set("Garden.Registration.Method", "Invitation");

        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);
        $actual = $this->userModel->getInvitationCount($userID);
        $this->assertEquals(0, $actual);
    }

    /**
     * Don't remove roles that are not the same sync type specified in the options.
     */
    public function testSaveRolesDontRemoveSync(): void
    {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ["sso"]]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID1], $roleIDs);
    }

    /**
     * You can overwrite the default sync type with an empty string.
     */
    public function testRoleSyncWithMultipleValues(): void
    {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ["sso", ""]]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->ssoRoleID1], $roleIDs);
    }

    /**
     * You can switch a role sync and it should change, but leave the original intact.
     */
    public function testRoleSyncSwitch(): void
    {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ["sso"]]);
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID2], [\UserModel::OPT_ROLE_SYNC => ["sso"]]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID2], $roleIDs);
    }

    /**
     * You should be able to control the role sync behavior through `UserModel::save()`.
     */
    public function testRoleSyncThroughUserSave(): void
    {
        $r = $this->userModel->save(
            [
                "UserID" => $this->memberID,
                "RoleID" => [$this->ssoRoleID1],
            ],
            [
                UserModel::OPT_SAVE_ROLES => true,
                UserModel::OPT_ROLE_SYNC => ["sso"],
            ]
        );

        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID1], $roleIDs);
    }

    /**
     * Make sure the user model can insert and update without corrupting the validation.
     */
    public function testValidationCorruption(): void
    {
        $user = $this->dummyUser();

        $id = $this->userModel->save($user);
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
        $this->assertGreaterThan(0, $id);

        // Here we shouldn't get an email or password required error.
        $r = $this->userModel->save(["UserID" => $id, "Name" => $user["Name"] . "Updated"]);
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
        $this->assertNotFalse($r);

        // Here we should get an email required error.
        $user2 = $this->dummyUser();
        unset($user2["Email"]);
        $id2 = $this->userModel->save($user2);
        $this->expectExceptionMessage("email is required");
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
    }

    /**
     * Make sure the username is required on inserts.
     */
    public function testUsernameRequiredOnInsert(): void
    {
        $user = $this->dummyUser();
        unset($user["Name"]);
        $id = $this->userModel->save($user);
        $this->expectExceptionMessage("name is required");
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
    }

    /**
     * Make sure the password strength is checked on inserts.
     */
    public function testPasswordStrengthCheckedOnInsert(): void
    {
        // Create a user with a weak password.
        $user = $this->dummyUser(["Password" => "123"]);
        $id = $this->userModel->save($user);
        $this->expectExceptionMessage("The password is too weak.");
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
    }

    /**
     * Test that a welcome email was properly sent.
     */
    public function testWelcomeEmailQuery(): void
    {
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $this->userModel->sendWelcomeEmail($userID, $user["Password"], "Add");
        $email = $this->assertEmailSentTo($user["Email"]);
        parse_str(Http::createFromString($email->template->getButtonUrl())->getQuery(), $query);
        $this->assertArraySubsetRecursive(
            [
                "vn_medium" => "email",
                "vn_campaign" => "welcome",
                "vn_source" => "add",
            ],
            $query
        );
    }

    /**
     * Test UserModel::searchByName().
     */
    public function testSearchByName(): void
    {
        $userA = [
            "Name" => "test_userSearch",
            "Email" => "test_userSearch@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];

        $userB = [
            "Name" => "testuserSearch",
            "Email" => "testuserSearch@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userIDA = $this->userModel->save($userA);
        $this->userModel->save($userB);

        $result = $this->userModel->searchByName($userA["Name"] . "*");
        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($userIDA, $row["UserID"]);
        $this->assertEquals(1, $result->numRows());
    }

    /**
     * Test `UserModel::saveIP()`.
     *
     * @param string $ip
     * @param bool $expected
     * @dataProvider provideSaveIPTests
     */
    public function testSaveIP(string $ip, bool $expected)
    {
        $id = $this->createUserFixture(self::ROLE_MEMBER);

        $r = $this->userModel->saveIP($id, $ip);
        $this->assertSame($expected, $r);
        $this->assertSame($expected, $r);
        $ips = $this->userModel->getIPs($id);

        if ($expected) {
            $this->assertContains($ip, $ips);
        } else {
            $this->assertNotContains($ip, $ips);
        }
    }

    /**
     * @return array
     */
    public function provideSaveIPTests(): array
    {
        $r = [["127.3.3.1", true], ["0.0.0.0", false]];
        return array_column($r, null, 0);
    }

    /**
     * Test UserModel::Merge().
     */
    public function testMergeUsers(): void
    {
        // User to merge
        $oldUser = $this->dummyUser();
        $newUser = $this->dummyUser();
        // Random conversation user.
        $randomUser = $this->dummyUser();

        $oldUserID = $this->userModel->save($oldUser);
        $newUserID = $this->userModel->save($newUser);
        $randomUserID = $this->userModel->save($randomUser);

        // Create an activity for old user.
        $this->activityModel->save([
            "ActivityUserID" => $oldUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_SKIPPED,
            "NotifyUserID" => $randomUserID,
        ]);

        // Create a discussion for old user.
        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "valid discussion",
            "Format" => "markdown",
            "InsertUserID" => $oldUserID,
        ]);

        // Save counts before merging users.
        $discussionCountBefore = $this->discussionModel->getCount(["d.InsertUserID" => $newUserID]);
        $commentCountBefore = $this->commentModel->getCountWhere([
            "DiscussionID" => $discussionID,
            "InsertUserID" => $newUserID,
        ]);

        $this->commentModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "Text",
            "InsertUserID" => $oldUserID,
        ]);

        // Create a conversation for old user.
        $conversationID = $this->conversationModel->save([
            "Format" => "Text",
            "Body" => "Creating conversation",
            "InsertUserID" => $oldUserID,
            "RecipientUserID" => [$randomUserID],
        ]);

        $conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);

        $this->conversationMessageModel->save([
            "ConversationID" => $conversation["ConversationID"],
            "Format" => "Text",
            "Body" => "This is a test message",
        ]);

        $activityCountBeforeOldUser = $this->activityModel->getCount([], $oldUserID);

        // Merge the 2 users.
        $result = $this->userModel->merge($oldUserID, $newUserID);

        // Verify all counts are correct after merging the users.=
        $activityCountAfter = $this->activityModel->getCount([], $newUserID);
        $this->assertEquals($activityCountBeforeOldUser, $activityCountAfter);
        $discussionCountAfter = $this->discussionModel->getCount(["d.InsertUserID" => $newUserID]);
        $commentCountAfter = $this->commentModel->getCountWhere([
            "DiscussionID" => $discussionID,
            "InsertUserID" => $newUserID,
        ]);
        $actualDiscussionCount = $result["After"]["NewUser"]["CountDiscussions"];
        $actualCommentCount = $result["After"]["NewUser"]["CountComments"];
        $this->assertEquals($discussionCountBefore + $discussionCountAfter, $actualDiscussionCount);
        $this->assertEquals($commentCountBefore + $commentCountAfter, $actualCommentCount);
        $this->assertNotEmpty($result["MergeID"]);
    }

    /**
     * Test the moderation dashboard's user list for a crypted password column.
     */
    public function testDashboardUserListPassword(): void
    {
        // As an admin...
        $this->getSession()->start($this->adminID);

        // on legacy table only, we don't have password column on new one (even on the existing one?)
        // cleanup NewUserManagement, will be gone here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release the new page/table
        $this->runWithConfig(["Feature.NewUserManagement.Enabled" => false], function () {
            $html = $this->bessy()->getHtml("/dashboard/user", [], ["deliveryType" => DELIVERY_TYPE_ALL]);

            // There should be a "Password" column.
            $html->assertCssSelectorTextContains("#Users.table-data", "Password");
        });
    }

    /**
     * Test ban by crypted password in the user moderation dashboard.
     */
    public function testDashboardAddUndecodedPasswordCustomBan(): void
    {
        // Load users.
        $users = $this->userModel->getLike()->resultArray();

        // We pick a user to ban.
        $userToBan = end($users);

        $this->assertNotEmpty($userToBan["Password"]);
        $this->assertEquals(0, $userToBan["Banned"]);

        // As an admin...
        $this->getSession()->start($this->adminID);

        $formValues = [
            "BanType" => "Password",
            "BanValue" => $userToBan["Password"],
            "Notes" => "We are banning " . $userToBan["Password"],
        ];

        $this->bessy()->post("/settings/bans/add", $formValues);

        // Reload the data of the banned user, for verification's sake.
        $bannedUser = $this->userModel->getID($userToBan["UserID"], DATASET_TYPE_ARRAY);

        $this->assertEquals(2, $bannedUser["Banned"]);
    }

    /**
     * Test user lookup by undecoded password in the user moderation dashboard.
     */
    public function testSearchDashboardUserByPassword(): void
    {
        // on legacy table only, we don't have password column on new one (even on the existing one?)
        // cleanup NewUserManagement, will be gone here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release the new page/table
        $this->runWithConfig(["Feature.NewUserManagement.Enabled" => false], function () {
            // Load users.
            $users = $this->userModel->getLike()->resultArray();

            // We pick 2 users with different Passwords
            $firstUser = reset($users);
            $lastUser = end($users);
            $this->assertNotEquals($firstUser["Password"], $lastUser["Password"]);

            // As an admin...
            $this->getSession()->start($this->adminID);

            // We do a search for one, confirm the other is not listed.
            $formValues = [
                "Keywords" => $firstUser["Password"],
            ];

            $html = $this->bessy()->getHtml("/dashboard/user/browse", $formValues, [
                "deliveryType" => DELIVERY_TYPE_ALL,
            ]);
            $html->assertCssSelectorTextContains("#Users.table-data", $firstUser["Name"]);
            $html->assertCssSelectorNotTextContains("#Users.table-data", $lastUser["Name"]);

            // We do a search for the other one & confirm the first one is not listed.
            $formValues = [
                "Keywords" => $lastUser["Password"],
            ];

            $html = $this->bessy()->getHtml("/dashboard/user/browse", $formValues, [
                "deliveryType" => DELIVERY_TYPE_ALL,
            ]);
            $html->assertCssSelectorTextContains("#Users.table-data", $lastUser["Name"]);
            $html->assertCssSelectorNotTextContains("#Users.table-data", $firstUser["Name"]);
        });
    }

    /**
     * PII should be blanked when a user is soft-deleted.
     */
    public function testRemovePseudoPIIOnDelete(): void
    {
        $id = $this->createUserFixture(static::ROLE_MEMBER);
        $date = CurrentTimeStamp::getMySQL();
        CurrentTimeStamp::mockTime($date);
        $ip = "127.0.0.1";

        $set = [
            "Photo" => "https://example.com/test.jpg",
            "Title" => "Foo",
            "Location" => "Bar",
            "About" => "A simple story.",
            "DiscoveryText" => "test",
            "DateOfBirth" => $date,
            "DateFirstVisit" => $date,
            "DateLastActive" => $date,
            "InsertIPAddress" => $ip,
            "LastIPAddress" => $ip,
        ];

        $this->userModel->setField($id, $set);

        $user = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($set, $user);

        $r = $this->userModel->deleteID($id);
        $this->assertTrue($r);

        $deletedUser = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        foreach ($set as $field => $value) {
            $this->assertEmpty($deletedUser[$field]);
        }

        $this->assertSame(t("[Deleted User]"), $deletedUser["Name"]);
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * Test UserModel::RateLimit()
     */
    public function testRateLimit(): void
    {
        $this->runWithConfig(
            [
                "Cache.Enabled" => false,
            ],
            function () {
                $rd = rand(1, 1000);
                $userIDA = $this->createUser("userA$rd");
                $userIDB = $this->createUser("userB$rd");
                $userA = $this->userModel->getID($userIDA, DATASET_TYPE_ARRAY);
                $userB = $this->userModel->getID($userIDB, DATASET_TYPE_ARRAY);
                $this->config->set("Garden.User.RateLimit", 1);
                $this->userModel->saveAttribute((int) $userIDA, ["LoginRate" => 1]);
                $result = UserModel::rateLimit($userA);
                $this->assertTrue($result);
                $this->config->set("Garden.User.RateLimit", 100);
                $this->userModel->saveAttribute($userIDB, ["LastLoginAttempt" => now()]);
                try {
                    UserModel::rateLimit($userB);
                } catch (\Gdn_UserException $e) {
                    $this->assertEquals("You are trying to log in too often. Slow down!.", $e->getMessage());
                }
            }
        );
    }

    /**
     * Test UserModel::RateLimit()
     */
    public function testRateLimitDisabled(): void
    {
        $this->runWithConfig(
            [
                "Cache.Enabled" => false,
            ],
            function () {
                $newUserId = $this->createUser("newUserRateLimitDisabled" . rand(1, 100000));
                $user = $this->userModel->getID($newUserId, DATASET_TYPE_ARRAY);
                $this->config->set("Garden.User.RateLimit", 0);
                $this->userModel->saveAttribute($newUserId, ["LoginRate" => 2]);
                $result = UserModel::rateLimit($user);
                $this->assertTrue($result);
            }
        );
    }

    /**
     * Test that user sessions are cleared upon password reset.
     */
    public function testClearSessionsUponPasswordReset(): void
    {
        // Create & insert a session's data for a Member user.
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            "Attributes" => [],
        ];
        $sessionID = $this->sessionModel->insert($session);
        // Fetch the member's session data.
        $memberSession = $this->sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);
        // Assert that the session data corresponds to the correct SessionID & UserID.
        $this->assertArraySubsetRecursive(
            [
                "SessionID" => $sessionID,
                "UserID" => $this->memberID,
            ],
            $memberSession
        );
        $this->userModel->incrementLoginAttempt($this->memberID);

        // Reset the user password (should clear this user's session).
        $this->userModel->passwordReset($this->memberID, "098+_ThisIsANewPassword_+123");
        // Clear suspension on password reset.
        $loggingAttempts = $this->userModel->getAttribute($this->memberID, "LoggingAttempts");
        $dateLastFailedLogin = $this->userModel->getAttribute($this->memberID, "DateLastFailedLogin", null);
        $this->assertEquals(0, $loggingAttempts);
        $this->assertNull($dateLastFailedLogin);
        // Try to fetch the member's session data again.
        $memberSession = $this->sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);
        // Assert the session doesn't exist anymore.
        $this->assertFalse($memberSession);
    }

    /**
     * Test various usages of UserModel's passwordRequest() function.
     *
     * @param bool $captchaPluginEnabled
     * @param bool $checkCaptchaOption
     * @param bool $useExistingUser
     * @param bool $expectedPasswordRequestResult
     * @param array $expectedValidationResults
     * @return void
     *
     * @dataProvider passwordRequestDataProvider
     */
    public function testPasswordRequest(
        bool $validateCaptcha,
        bool $checkCaptchaOption,
        bool $useExistingUser,
        bool $expectedPasswordRequestResult,
        array $expectedValidationResults
    ): void {
        $existingUserEmail = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY)["Email"];
        $nonExistentUserEmail = "nonexistenuser@email.com";

        $userEmail = $useExistingUser ? $existingUserEmail : $nonExistentUserEmail;

        [$passwordRequestResult, $validationResults] = $this->runWithConfig(
            ["Garden.Registration.SkipCaptcha" => !$validateCaptcha],
            function () use ($userEmail, $checkCaptchaOption) {
                // Request a password reset.
                $passwordRequestResult = $this->userModel->passwordRequest($userEmail, [
                    "checkCaptcha" => $checkCaptchaOption,
                ]);
                // Get validation errors if there are any.
                $validationResults = $this->userModel->getValidation()->results();
                return [$passwordRequestResult, $validationResults];
            }
        );
        // Assert we got the expected password request result as well as the expected validation results.
        $this->assertEquals($expectedPasswordRequestResult, $passwordRequestResult);
        $this->assertArraySubsetRecursive($expectedValidationResults, $validationResults);
    }

    /**
     * Test cases data provider for testPasswordRequest().
     * Each record should have the following structure:
     *  [
     *      true/false,         // Captcha validation enabled.
     *      true/false,         // checkCaptcha option value.
     *      true/false,         // Use existing user.
     *      true/false,         // Expected password request result.
     *      ['expected node' => [0 => 'expected error message']]   // Expected validation results
     *  ]
     */
    public function passwordRequestDataProvider(): array
    {
        return [
            "Captcha validation ENABLED, checkCaptcha FALSE & existing USER." => [true, false, true, true, []],
            "Captcha validation ENABLED, checkCaptcha TRUE & existing USER." => [
                true,
                true,
                true,
                false,
                [
                    "Garden.Registration.CaptchaPublicKey" => [
                        0 => "The captcha was not completed correctly. Please try again.",
                    ],
                ],
            ],
            "Captcha validation DISABLED, checkCaptcha TRUE & non-existent USER." => [
                false,
                true,
                false,
                true,
                ["email" => [0 => "Couldn't find an account associated with that email/username."]],
            ],
            "Captcha validation DISABLED, checkCaptcha TRUE & existing USER." => [false, true, true, true, []],
        ];
    }

    /**
     * Test fetching userIDs for a name with duplicate names.
     */
    public function testGetUserIDsForUserNames()
    {
        $user1 = $this->createUser("user1");
        $deleted1 = $this->createUser("Deleted User");

        // Hack around to create 2 users with the same name.
        $deleted2 = $this->createUser("Deleted User2");
        $this->userModel->setField($deleted2, "Name", "Deleted User");

        $actual = $this->userModel->getUserIDsForUserNames(["user1", "Deleted User"]);
        $this->assertEquals(
            [
                "user1" => [
                    "userID" => $user1,
                    "name" => "user1",
                ],
                "Deleted User" => [
                    "userID" => $deleted1,
                    "name" => "Deleted User",
                ],
            ],
            $actual
        );
    }

    /**
     * Provides test data for testDataNormalizedForInsertedUsers
     *
     * @return \Generator
     */
    public function provideInsertUserTestData(): \Generator
    {
        foreach (["ShowEmail", "Banned", "Verified"] as $field) {
            yield "$field with 1" => [$field, 1, 1];
            yield "$field with 0" => [$field, 0, 0];
            yield "$field with string `true`" => [$field, "true", 1];
            yield "$field with string `false`" => [$field, "false", 0];
            yield "$field with boolean `true`" => [$field, true, 1];
            yield "$field with boolean `false`" => [$field, false, 0];
        }
    }

    /**
     * Tests that certain fields are normalized when inserted into the user table.
     * Test includes methods insertForBasic, insertForApproval and insertForInvite
     *
     * @param string $field
     * @param mixed $inputValue
     * @param mixed $expectedDbValue
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @dataProvider provideInsertUserTestData
     */
    public function testDataNormalizedForInsertedUsers(string $field, $inputValue, $expectedDbValue)
    {
        $configuration = self::container()->get(ConfigurationInterface::class);

        $doAssertions = function ($userID) use ($field, $expectedDbValue) {
            ModelUtils::validationResultToValidationException($this->userModel);
            $this->assertIsNumeric($userID);
            $user = $this->userModel->getID($userID);
            $this->assertEquals($expectedDbValue, $user->$field);
        };

        $userData = $this->dummyUser([$field => $inputValue]);
        $userID = $this->userModel->insertForBasic($userData);
        $doAssertions($userID);

        $userData = $this->dummyUser([$field => $inputValue]);
        $userID = $this->userModel->insertForApproval($userData);
        $doAssertions($userID);

        $configuration->set("Garden.Registration.Method", "Invitation");
        $configuration->set("Garden.Email.Disabled", true);
        $userData = $this->dummyUser([$field => $inputValue]);
        $invitation = $this->api()
            ->post("/invites", ["email" => $userData["Email"]])
            ->getBody();
        $userData["InvitationCode"] = $invitation["code"];
        $userID = $this->userModel->insertForInvite($userData);
        $doAssertions($userID);
    }

    /**
     * Tests that when a user updates their password, their other open sessions are invalidated.
     */
    public function testPasswordChangeInvalidatesOtherSessions()
    {
        $sessionModel = new \SessionModel();
        $userID = $this->createUser(__FUNCTION__);

        $session = $this->getSession();
        $session->start($userID);

        // Clear cookie identity
        \Gdn::factory("Identity")->setIdentity();

        $session->start($userID);

        // We should have 2 sessions in the database for the same user
        $this->assertCount(2, $sessionModel->getSessions($userID));

        // Password change
        $this->userModel->save([
            "UserID" => $userID,
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ]);

        $sessions = $sessionModel->getSessions($userID);

        // Now we should just have 1 session in the database with its sessionID matching the current session
        $this->assertCount(1, $sessions);
        $this->assertEquals(\Gdn::authenticator()->getSession(), $sessions[0]["SessionID"]);

        $session->end();
    }

    /**
     * Tests that getUserIDsForIPAddresses returns an array of userIDs for users associated with any of the given IP addresses.
     */
    public function testGetUserIDsForIPAddresses()
    {
        $user1ID = $this->createUser(__FUNCTION__ . "1");
        $user2ID = $this->createUser(__FUNCTION__ . "2");
        $this->userModel->saveIP($user1ID, "101.102.103.104");
        $this->userModel->saveIP($user2ID, "2001:db8:3333:4444:5555:6666:7777:8888");
        $userIDs = $this->userModel->getUserIDsForIPAddresses([
            "101.102.103.104",
            "2001:db8:3333:4444:5555:6666:7777:8888",
        ]);
        $this->assertSame(array_map("intval", [$user1ID, $user2ID]), $userIDs);
    }

    /**
     * Test giveRolesByEmail method.
     */
    public function testGiveRolesByEmail()
    {
        $userID = $this->createUser(__FUNCTION__);
        $role = $this->createRole(["Domains" => "hl.com example.com vanilla.com"]);
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $this->userModel->giveRolesByEmail($user);
        $roles = $this->userModel->getRoleIDs($userID);
        // Role not assigned when user's email is not confirmed.
        $this->assertNotContains($role["roleID"], $roles);

        $this->userModel->setField($userID, "Confirmed", 1);
        $roles = $this->userModel->getRoleIDs($userID);
        // Role is assigned when user's email is confirmed.
        $this->assertContains($role["roleID"], $roles);
    }

    /**
     * Tests that searchCount returns the correct result with or without an explicit max limit.
     */
    public function testSearchCount()
    {
        $role = $this->createRole();
        $this->createUserByApi(["roleID" => [$role["roleID"]]]);
        $this->createUserByApi(["roleID" => [$role["roleID"]]]);
        $this->createUserByApi(["roleID" => [$role["roleID"]]]);
        $count = $this->userModel->searchCount(["roleID" => $role["roleID"]]);
        $this->assertSame(3, $count);

        // Test with explicit upper limit.
        $this->runWithConfig(["Vanilla.APIv2.MaxCount" => 2], function () use ($role) {
            $count = $this->userModel->searchCount(["roleID" => $role["roleID"]]);
            $this->assertSame(2, $count);
        });
    }

    /**
     * Test user permission for a category
     *
     * @return void
     */
    public function testCategoryViewPermission()
    {
        $category = $this->createCategory(["CustomPermissions" => true]);
        $viewPermission = $this->userModel->getCategoryViewPermission(0, $category["categoryID"]);
        $this->assertTrue($viewPermission);

        $this->api()->patch("/roles/" . \RoleModel::GUEST_ID, [
            "permissions" => [
                [
                    "id" => $category["categoryID"],
                    "type" => "category",
                    "permissions" => [
                        "discussions.view" => false,
                    ],
                ],
            ],
        ]);

        $viewPermission = $this->userModel->getCategoryViewPermission(0, $category["categoryID"]);
        $this->assertFalse($viewPermission);

        $viewPermission = $this->userModel->getCategoryViewPermission(2, 0);
        $this->assertFalse($viewPermission);
        $viewPermission = $this->userModel->getCategoryViewPermission(2, 999);
        $this->assertTrue($viewPermission);
    }

    /**
     * This tests that user events for inserts are dispatched after profile fields are updated
     * so that listeners have access to profile fields.
     *
     * @return void
     */
    public function testProfileFieldsUpdatedBeforeEventDispatch()
    {
        $this->enableFeature(ProfileFieldModel::FEATURE_FLAG);
        $profileField = $this->createProfileField(["registrationOptions" => "optional"]);
        $callbackFired = false;

        $userEventCallback = function (UserEvent $event) use ($profileField, &$callbackFired) {
            $userID = $event->getUniquePrimaryKeyValue();
            $userProfileFields = $this->profileFieldModel->getUserProfileFields($userID);
            $this->assertArrayHasKey($profileField["apiName"], $userProfileFields);
            $this->assertEquals("hehehe", $userProfileFields[$profileField["apiName"]]);
            $callbackFired = true;

            return $event;
        };
        $this->getEventManager()->bind(UserEvent::class, $userEventCallback);
        $this->runWithConfig(["Garden.Registration.Method" => "Basic"], function () use ($profileField) {
            $this->bessy()->postHtml("/entry/register", [
                "Email" => "u105@example.com",
                "Name" => "u105",
                "Profile" => [
                    $profileField["apiName"] => "hehehe",
                ],
                "Password" => "passwordpassword",
                "PasswordMatch" => "passwordpassword",
                "TermsOfService" => "1",
            ]);
        });
        $this->getEventManager()->unbind(UserEvent::class, $userEventCallback);
        $this->assertTrue($callbackFired);

        $this->disableFeature(ProfileFieldModel::FEATURE_FLAG);
    }

    /**
     * Test that user email changes by an admin user are saved to the user email column directly.
     *
     * @return array
     * @throws \Gdn_UserException
     */
    public function testUserEmailChangesByAdminAreUpdatedDirectly(): array
    {
        // First create the user with confirmed set to true.
        $user = $this->createUserByApi(["email" => "confirm-reset-test@example.com", "emailConfirmed" => true]);
        $this->assertTrue($user["emailConfirmed"]);

        // Calling the save function as an admin user, the email is updated directly with no pending attribute needed.
        $this->userModel->save(["UserID" => $user["userID"], "Email" => "confirm-reset-test-1@example.com"]);
        $userFromModel = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $userFromModel["Confirmed"]);
        $this->assertArrayNotHasKey("EmailKey", $userFromModel["Attributes"]);
        $this->assertArrayNotHasKey("PendingEmail", $userFromModel["Attributes"]);

        return $user;
    }

    /**
     * Test that user email changes by the user themselves is stored as a pending email and a confirmation email is sent.
     *
     * @depends testUserEmailChangesByAdminAreUpdatedDirectly
     * @param array $user
     * @return array
     */
    public function testUserEmailChangesByUserAreSavedAsPendingEmail(array $user): array
    {
        // For a user updating their own email, test that EmailKey and PendingEmail are stored as attributes.
        $this->runWithUser(function () use ($user) {
            $this->userModel->save(["UserID" => $user["userID"], "Email" => "confirm-reset-test-2@example.com"]);
            $userFromModel = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
            $this->assertEquals(1, $userFromModel["Confirmed"]);
            $this->assertArrayHasKey("EmailKey", $userFromModel["Attributes"]);
            $this->assertArrayHasKey("PendingEmail", $userFromModel["Attributes"]);
            $this->assertSame("confirm-reset-test-2@example.com", $userFromModel["Attributes"]["PendingEmail"]);
        }, $user);
        return $user;
    }

    /**
     * Test that when a user has a pending email, and they update the email using the original email value,
     * the pending email value is cleared.
     *
     * @depends testUserEmailChangesByUserAreSavedAsPendingEmail
     * @param array $user
     * @return void
     */
    public function testUserEmailRestoreByUserClearsPendingEmail(array $user)
    {
        // For a user restoring their email to the original value, test that PendingEmail is cleared.
        $this->runWithUser(function () use ($user) {
            $this->userModel->save(["UserID" => $user["userID"], "Email" => "confirm-reset-test-1@example.com"]);
            $userFromModel = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
            $this->assertEquals(1, $userFromModel["Confirmed"]);
            $this->assertArrayHasKey("EmailKey", $userFromModel["Attributes"]);
            $this->assertNull($userFromModel["Attributes"]["PendingEmail"]);
        }, $user);
    }

    /**
     * Test that when a user updates their email, and they click the confirmation link to confirm the email,
     * the pending email is cleared and their email address is updated.
     *
     * @return void
     */
    public function testEmailConfirmationLinkClearsPendingEmail()
    {
        $user = $this->createUserByApi();
        $originalEmail = $user["email"];
        $this->runWithUser(function () use ($user, $originalEmail) {
            $this->userModel->save(["UserID" => $user["userID"], "Email" => "confirm-reset-test-3@example.com"]);
            $userFromModel = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
            $this->assertArrayHasKey("PendingEmail", $userFromModel["Attributes"]);
            $this->assertSame("confirm-reset-test-3@example.com", $userFromModel["Attributes"]["PendingEmail"]);
            $this->assertSame($originalEmail, $userFromModel["Email"]);
            $this->assertEmailSentTo("confirm-reset-test-3@example.com");

            $this->bessy()->get("/entry/emailconfirm/{$user["userID"]}/{$userFromModel["Attributes"]["EmailKey"]}");
            $userFromModel = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
            $this->assertSame("confirm-reset-test-3@example.com", $userFromModel["Email"]);
            $this->assertArrayNotHasKey("PendingEmail", $userFromModel["Attributes"]);
        }, $user);
    }
}

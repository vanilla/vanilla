<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use League\Uri\Http;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\ModelUtils;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\Bootstrap;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;
use Garden\EventManager;
use Vanilla\Dashboard\Events\UserEvent;
use \UserModel;
use VanillaTests\VanillaTestCase;
use ActivityModel;

/**
 * Test {@link UserModel}.
 */
class UserModelTest extends SiteTestCase {
    use EventSpyTestTrait;

    public static $addons = ['vanilla', 'dashboard', 'conversations'];

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

    /** @var \Gdn_Configuration */
    private $config;

    /**
     * @inheritDoc
     */
    public function setup(): void {
        parent::setUp();

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;

        $this->container()->call(function (
            EventManager $eventManager,
            \Gdn_Configuration $config
        ) {
            $eventManager->addListenerMethod(self::class, "handleUserEvent");
            $this->config = $config;
        });

        $this->config->set([
            // Relax username validation.
            'Garden.User.ValidationRegexPattern' => '`^[a-zA-Z0-9_ ]*$`'
        ], null);
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        $this->commentModel = $this->container()->get(\CommentModel::class);
        $this->conversationModel = $this->container()->get(\ConversationModel::class);
        $this->conversationMessageModel = $this->container()->get(\ConversationMessageModel::class);

        // Add a couple of test SSO roles.
        $this->ssoRoleID1 = $this->defineRole(['Name' => 'SSO 1', 'Sync' => 'sso']);
        $this->ssoRoleID2 = $this->defineRole(['Name' => 'SSO 2', 'Sync' => 'sso']);

        $this->createUserFixtures();
    }

    /**
     * A test listener that increments the counter.
     *
     * @param UserEvent $e
     * @return UserEvent
     */
    public function handleUserEvent(UserEvent $e): UserEvent {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Adds crypted "password" to the allowed banTypes of the "Ban Rules" dashboard interface.
     *
     * @param array $banTypes
     * @return array
     */
    public function settingsController_listBanTypes(array $banTypes): array {
        $banTypes['Password'] = t('Password');
        return $banTypes;
    }

    /**
     * Add ordering users by Email in the dashboard's users list.
     *
     * @param array $allowedSorting
     * @return array
     */
    public function userController_usersListAllowedSorting(array $allowedSorting): array {
        $allowedSorting['Password'] = 'desc';
        return $allowedSorting;
    }

    /**
     * Add crypted password string to the dashboard's users list search query.
     *
     * @param array $whereCriterias
     * @param string $keywords
     * @return array
     */
    public function userModel_searchKeyWords_handler(array $whereCriterias, string $keywords): array {
        $whereCriterias['where']['u.Password'] = $keywords;

        return $whereCriterias;
    }

    /**
     * Add uncrypted "Password" to the possible ban query.
     *
     * @param array $result
     * @param array $ban
     * @return array
     */
    public function banModel_banWhere_handler(array $result, array $ban): array {
        switch (strtolower($ban['BanType'])) {
            case 'password':
                $result['u.Password'] = $ban['BanValue'];
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
    public function base_userCell_handler($sender, $args) {
        // If we have user data, we create a cell containing the crypted password.
        if (isset($args['User'])) {
            echo '<td>' . $args['User']->Password ?? '' . '</td>';
        } else {
            echo '<th class="column-md">' . t('Password') . '</th>';
        }
    }

    /**
     * Create a dummy user for testing.
     *
     * @param array $overrides
     * @return array
     */
    protected function dummyUser(array $overrides = []): array {
        $user = self::sprintfCounter(array_replace(
            ['Name' => 'user%s', 'Email' => "user%s@example.com", 'Password' => 'foo123'],
            $overrides
        ));
        return $user;
    }

    /**
     * Make sure the setup fixtures work.
     */
    public function testSetUp() {
        $this->assertIsInt($this->ssoRoleID1);
        $this->assertIsInt($this->ssoRoleID2);
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void {
        $user = [
            "Name" => "testuser",
            "Email" => "testuser@example.com",
            "Password" => "vanilla"
        ];

        $userID = $this->userModel->save($user);
        $this->userModel->deleteID($userID);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void {
        $user = [
            "Name" => "testuser2",
            "Email" => "testuser2@example.com",
            "Password" => "vanilla"
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
    public function testSaveUpdateEventDispatched(): void {
        $user = [
            "Name" => "testuser3",
            "Email" => "testuser3@example.com",
            "Password" => "vanilla"
        ];
        $userUpdate = [
            "Name" => "testuser3",
            "Email" => "testuser4@example.com",
            "Password" => "vanilla"
        ];
        $userID = $this->userModel->save($user);
        $user = (array)$this->userModel->getID($userID);
        $userUpdate['UserID'] = $user['UserID'];
        $this->userModel->save($userUpdate);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }

    /**
     * Test searching for users by a role keyword.
     */
    public function testSearchByRole(): void {
        $roles = $this->getRoles();
        $adminRole = $roles["Administrator"];

        // Make sure we have at least one non-admin.
        $this->userModel->save([
            "Name" => __FUNCTION__,
            "Email" => __FUNCTION__ . "@example.com",
            "Password" => "vanilla",
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
    public function testSearch(string $username, string $keywords, int $expectedCount): void {
        if ($username) {
            $this->createUser($username);
        }
        $result = $this->userModel->search(['Keywords' => $keywords]);
        $this->assertEquals($expectedCount, $result->numRows());
    }

    /**
     * Test cases data provider
     */
    public function searchDataProvider(): array {
        return [
            'regular search' => ['searcha', 'searcha', 1],
            'regular search 2' => ['abcsearcha', 'searcha', 1],
            'search user with space' => ['search a', 'search a', 1],
            'user with underscore' => ['search_a', 'search_a', 1],
            'wildcard search both' => ['', '%searcha%', 2],
            'wildcard search left' => ['', '%searcha', 2],
            'wildcard search right' => ['', 'searcha%', 1],
            'wildcard both username with space' => ['', '%search a%', 1],
            'wildcard left username with space'=> ['', '%search a', 1],
            'wildcard right username with space' => ['', 'search a%', 1],
            'wildcard right multiple users' => ['', 'search%', 3],
            ];
    }

    /**
     * Create  User.
     *
     * @param string $userName
     */
    private function createUser(string $userName): void {
        $rand = rand(10, 1000);
        $user = [
            "Name" => $userName,
            "Email" => $rand."test@example.com",
            "Password" => "vanilla"
        ];
        $this->userModel->save($user);
    }

    /**
     * I should be able to save an absolute list of role IDs.
     */
    public function testSaveRoles() {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);
        $roleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER)], $roleIDs, 'The test user doesn\'t have the right roles.');

        $setRoleIDs = [$this->roleID(Bootstrap::ROLE_ADMIN)];
        $this->userModel->saveRoles($userID, $setRoleIDs, [\UserModel::OPT_LOG_ROLE_CHANGES => true]);
        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertSame($setRoleIDs, $newRoleIDs);

        $this->assertLog(['event' => 'role_add', 'role' => Bootstrap::ROLE_ADMIN]);
        $this->assertLog(['event' => 'role_remove', 'role' => Bootstrap::ROLE_MEMBER]);
    }

    /**
     * I should be able to add roles.
     */
    public function testAddRoles(): int {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);

        $this->userModel->addRoles($userID, [$this->roleID(Bootstrap::ROLE_ADMIN)], true);
        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertEqualsCanonicalizing(
            [$this->roleID(Bootstrap::ROLE_MEMBER), $this->roleID(Bootstrap::ROLE_ADMIN)],
            $newRoleIDs
        );

        $this->assertLog(['event' => 'role_add', 'role' => Bootstrap::ROLE_ADMIN]);

        return $userID;
    }

    /**
     * I should be able to remove roles from a user.
     *
     * @param int $userID
     * @depends testAddRoles
     */
    public function testRemoveRoles(int $userID): void {
        $this->userModel->removeRoles($userID, [$this->roleID(Bootstrap::ROLE_MEMBER)], true);

        $newRoleIDs = $this->userModel->getRoleIDs($userID);
        $this->assertEqualsCanonicalizing(
            [$this->roleID(Bootstrap::ROLE_ADMIN)],
            $newRoleIDs
        );
        $this->assertLog(['event' => 'role_remove', 'role' => Bootstrap::ROLE_MEMBER]);
    }

    /**
     * Test UserModel->getInvitationCount
     */
    public function testGetInvitationCount(): void {
        \Gdn::config()->set('Garden.Registration.Method', 'Invitation');

        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);
        $actual = $this->userModel->getInvitationCount($userID);
        $this->assertEquals(0, $actual);
    }

    /**
     * Don't remove roles that are not the same sync type specified in the options.
     */
    public function testSaveRolesDontRemoveSync(): void {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ['sso']]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID1], $roleIDs);
    }

    /**
     * You can overwrite the default sync type with an empty string.
     */
    public function testRoleSyncWithMultipleValues(): void {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ['sso', '']]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->ssoRoleID1], $roleIDs);
    }

    /**
     * You can switch a role sync and it should change, but leave the original intact.
     */
    public function testRoleSyncSwitch(): void {
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID1], [\UserModel::OPT_ROLE_SYNC => ['sso']]);
        $this->userModel->saveRoles($this->memberID, [$this->ssoRoleID2], [\UserModel::OPT_ROLE_SYNC => ['sso']]);
        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID2], $roleIDs);
    }

    /**
     * You should be able to control the role sync behavior through `UserModel::save()`.
     */
    public function testRoleSyncThroughUserSave(): void {
        $r = $this->userModel->save([
            'UserID' => $this->memberID,
            'RoleID' => [$this->ssoRoleID1],
        ], [
            UserModel::OPT_SAVE_ROLES => true,
            UserModel::OPT_ROLE_SYNC => ['sso'],
        ]);

        $roleIDs = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER), $this->ssoRoleID1], $roleIDs);
    }

    /**
     * Make sure the user model can insert and update without corrupting the validation.
     */
    public function testValidationCorruption(): void {
        $user = $this->dummyUser();

        $id = $this->userModel->save($user);
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
        $this->assertGreaterThan(0, $id);

        // Here we shouldn't get an email or password required error.
        $r = $this->userModel->save(['UserID' => $id, 'Name' => $user['Name'].'Updated']);
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
        $this->assertNotFalse($r);

        // Here we should get an email required error.
        $user2 = $this->dummyUser();
        unset($user2['Email']);
        $id2 = $this->userModel->save($user2);
        $this->expectExceptionMessage('email is required');
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
    }

    /**
     * Make sure the username is required on inserts.
     */
    public function testUsernameRequiredOnInsert(): void {
        $user = $this->dummyUser();
        unset($user['Name']);
        $id = $this->userModel->save($user);
        $this->expectExceptionMessage('name is required');
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
    }

    /**
     * Test that a welcome email was properly sent.
     */
    public function testWelcomeEmailQuery(): void {
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $this->userModel->sendWelcomeEmail($userID, $user['Password'], 'Add');
        $email = $this->assertEmailSentTo($user['Email']);
        parse_str(Http::createFromString($email->template->getButtonUrl())->getQuery(), $query);
        $this->assertArraySubsetRecursive(
            [
                'vn_medium' => 'email',
                'vn_campaign' => 'welcome',
                'vn_source' => 'add',
            ],
            $query
        );
    }

    /**
     * Test UserModel::searchByName().
     */
    public function testSearchByName(): void {
        $userA = [
            "Name" => "test_userSearch",
            "Email" => "test_userSearch@example.com",
            "Password" => "vanilla"
        ];

        $userB = [
            "Name" => "testuserSearch",
            "Email" => "testuserSearch@example.com",
            "Password" => "vanilla"
        ];
        $userIDA = $this->userModel->save($userA);
        $this->userModel->save($userB);

        $result = $this->userModel->searchByName($userA['Name'].'*');
        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($userIDA, $row['UserID']);
        $this->assertEquals(1, $result->numRows());
    }

    /**
     * Test `UserModel::saveIP()`.
     *
     * @param string $ip
     * @param bool $expected
     * @dataProvider provideSaveIPTests
     */
    public function testSaveIP(string $ip, bool $expected) {
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
    public function provideSaveIPTests(): array {
        $r = [
            ['127.3.3.1', true],
            ['0.0.0.0', false],
        ];
        return array_column($r, null, 0);
    }

    /**
     * Test UserModel::Merge().
     */
    public function testMergeUsers(): void {
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
            "NotifyUserID" => $randomUserID
        ]);

        // Create a discussion for old user.
        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "valid discussion",
            "Format" => "markdown",
            "InsertUserID" => $oldUserID
        ]);

        // Save counts before merging users.
        $discussionCountBefore = $this->discussionModel->getCount(['d.InsertUserID' => $newUserID]);
        $commentCountBefore = $this->commentModel->getCountWhere(['DiscussionID' => $discussionID, 'InsertUserID' => $newUserID]);

        $commentID = $this->commentModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "Text",
            "InsertUserID" => $oldUserID
        ]);

        // Update User's comment count.
        $this->commentModel->save2([$commentID], true, true, false);

        // Create a conversation for old user.
        $conversationID = $this->conversationModel->save([
            "Format" => "Text",
            "Body" => "Creating conversation",
            "InsertUserID" => $oldUserID,
            "RecipientUserID" => [$randomUserID]]);

        $conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);

        $this->conversationMessageModel->save([
            "ConversationID" => $conversation["ConversationID"],
            "Format" => "Text",
            "Body" => "This is a test message"
        ]);

        $activityCountBeforeOldUser = $this->activityModel->getCount($oldUserID);

        // Merge the 2 users.
        $result = $this->userModel->merge($oldUserID, $newUserID);

        // Verify all counts are correct after merging the users.=
        $activityCountAfter = $this->activityModel->getCount($newUserID);
        $this->assertEquals($activityCountBeforeOldUser, $activityCountAfter);
        $discussionCountAfter = $this->discussionModel->getCount(['d.InsertUserID' => $newUserID]);
        $commentCountAfter = $this->commentModel->getCountWhere(['DiscussionID' => $discussionID, 'InsertUserID' => $newUserID]);
        $actualDiscussionCount = $result['After']['NewUser']['CountDiscussions'];
        $actualCommentCount = $result['After']['NewUser']['CountComments'];
        $this->assertEquals($discussionCountBefore + $discussionCountAfter, $actualDiscussionCount);
        $this->assertEquals($commentCountBefore + $commentCountAfter, $actualCommentCount);
        $this->assertNotEmpty($result['MergeID']);
    }

    /**
     * Test the moderation dashboard's user list for a crypted password column.
     */
    public function testDashboardUserListPassword(): void {
        // As an admin...
        $this->getSession()->start($this->adminID);

        $html = $this->bessy()->getHtml('/dashboard/user', [], ['deliveryType' => DELIVERY_TYPE_ALL]);

        // There should be a "Password" column.
        $html->assertCssSelectorTextContains('#Users.table-data', 'Password');
    }

    /**
     * Test ban by crypted password in the user moderation dashboard.
     */
    public function testDashboardAddUndecodedPasswordCustomBan(): void {
        // Load users.
        $users = $this->userModel->getLike()->resultArray();

        // We pick a user to ban.
        $userToBan = end($users);

        $this->assertNotEmpty($userToBan['Password']);
        $this->assertEquals(0, $userToBan['Banned']);

        // As an admin...
        $this->getSession()->start($this->adminID);

        $formValues = [
            'BanType' => 'Password',
            'BanValue' => $userToBan['Password'],
            'Notes' => 'We are banning '.$userToBan['Password']
        ];

        $this->bessy()->post('/settings/bans/add', $formValues);

        // Reload the data of the banned user, for verification's sake.
        $bannedUser = $this->userModel->getID($userToBan['UserID'], DATASET_TYPE_ARRAY);

        $this->assertEquals(2, $bannedUser['Banned']);
    }

    /**
     * Test user lookup by undecoded password in the user moderation dashboard.
     */
    public function testSearchDashboardUserByPassword(): void {
        // Load users.
        $users = $this->userModel->getLike()->resultArray();

        // We pick 2 users with different Passwords
        $firstUser = reset($users);
        $lastUser = end($users);
        $this->assertNotEquals($firstUser['Password'], $lastUser['Password']);

        // As an admin...
        $this->getSession()->start($this->adminID);

        // We do a search for one, confirm the other is not listed.
        $formValues = [
            'Keywords' => $firstUser['Password'],
        ];

        $html = $this->bessy()->getHtml('/dashboard/user/browse', $formValues, ['deliveryType' => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorTextContains('#Users.table-data', $firstUser['Name']);
        $html->assertCssSelectorNotTextContains('#Users.table-data', $lastUser['Name']);

        // We do a search for the other one & confirm the first one is not listed.
        $formValues = [
            'Keywords' => $lastUser['Password'],
        ];

        $html = $this->bessy()->getHtml('/dashboard/user/browse', $formValues, ['deliveryType' => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorTextContains('#Users.table-data', $lastUser['Name']);
        $html->assertCssSelectorNotTextContains('#Users.table-data', $firstUser['Name']);
    }

    /**
     * PII should be blanked when a user is soft-deleted.
     */
    public function testRemovePseudoPIIOnDelete(): void {
        $id = $this->createUserFixture(static::ROLE_MEMBER);
        $date = CurrentTimeStamp::getMySQL();
        CurrentTimeStamp::mockTime($date);
        $ip = '127.0.0.1';

        $set = [
            'Photo' => 'https://example.com/test.jpg',
            'Title' => 'Foo',
            'Location' => 'Bar',
            'About' => 'A simple story.',
            'DiscoveryText' => 'test',
            'DateOfBirth' => $date,
            'DateFirstVisit' => $date,
            'DateLastActive' => $date,
            'InsertIPAddress' => $ip,
            'LastIPAddress' => $ip,
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

        $this->assertSame(t('[Deleted User]'), $deletedUser['Name']);
        CurrentTimeStamp::clearMockTime();
    }
}

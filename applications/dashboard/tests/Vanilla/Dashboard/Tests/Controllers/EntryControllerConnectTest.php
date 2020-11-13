<?php
/**
 * @noinspection PhpUnusedLocalVariableInspection
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 *
 */

namespace Vanilla\Dashboard\Tests\Controllers;

use EntryController;
use Garden\EventManager;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\Bootstrap;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the infamous `entry/connect` endpoint.
 *
 * The `EntryController::connect()` method handles most of Vanilla's SSO. It's an incredibly important endpoint and is a
 * good one for developers to understand. This test class includes a good dose of documentation in order to explain
 * entry/connect as best as possible.
 *
 * To begin with, let's go through the basic entry/connect process.
 *
 * 1. Early in the method, entry/connect fires the "connectData" event.
 * 2. SSO addons handle the event with `base_connectData_handler()` plugin methods. Usually they check `$args[0]` for
 *    their provider name or bail out.
 * 3. Addons authenticate their SSO any way their protocol dictates. This usually involves looking at values in
 *   `$sender->Request` for tokens, SSO responses, and whatnot. The addon usually throws a `Gdn_UserException` if
 *    something doesn't check out.
 * 4. The addon sets user information using `$sender->Form->setFormValue()`. Set any user field plus a "UniqueID" value
 *    to identify the user on future SSO's.
 * 5. Additionally, the add sets a "Provider" and "ProviderName" to tell entry/connect who's providing the SSO.
 * 6. Finally, the addon marks the SSO complete with a call to `$sender->setData('Verified', true)`.
 *
 * That's the basic process, but are a tonne of cases that need to be handled, errors, and/or config settings. To learn
 * more about those read the doc-blocks above the tests. You can also run any test through the debugger to see the code.
 *
 * NOTE: This `entry/connect` endpoint has been an absolute workhorse for Vanilla, but it is VERY legacy code. There are
 * a lot of bad habits and anti-patterns in the code. In fact, the `EntryController` was the main driving force behind
 * the Bessy test harness. We want to increase the quality of `entry/connect` by writing adequate tests for it. We also
 * want protection against future refactoring.
 *
 * Learn how `entry/connect` works from reading its code, but don't learn how to code there.
 */
class EntryControllerConnectTest extends VanillaTestCase {
    use SiteTestTrait, SetupTraitsTrait;

    protected const PROVIDER_KEY = 'ec-test';

    /**
     * @var \Gdn_AuthenticationProviderModel
     */
    private $authModel;

    /**
     * @var \Gdn_Configuration
     */
    private $config;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * @var array
     */
    private $existingUsers;

    //region Set up and tear down.
    /**
     * {@inheritDoc}
     *
     * Hey! Look near the bottom of this method for a list of config settings that SSO uses. It's a serendipitous list.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();
        $this->createUserFixtures();
        debug(true);

        $this->container()->call(function (
            \Gdn_AuthenticationProviderModel $authModel,
            \Gdn_Configuration $config,
            \Gdn_Session $session
        ) {
            $this->authModel = $authModel;
            $this->config = $config;
            $this->session = $session;
        });

        $this->authModel->delete([\Gdn_AuthenticationProviderModel::COLUMN_KEY => self::PROVIDER_KEY]);
        $this->authModel->save(
            [
                \Gdn_AuthenticationProviderModel::COLUMN_KEY => self::PROVIDER_KEY,
                \Gdn_AuthenticationProviderModel::COLUMN_ALIAS => self::PROVIDER_KEY,
                'AssociationSecret' => self::PROVIDER_KEY,
                'Name' => self::PROVIDER_KEY,
                'Trusted' => true,
            ],
            ['checkExisting' => true]
        );

        // Save some default SSO config settings to make testing easier and document the settings!
        $this->config->set([
            // Relax username validation so we don't have to worry during SSO testing.
            'Garden.User.ValidationRegexPattern' => '`.+`',
            // If you don't know what this is then you haven't been at Vanilla long.
            'Garden.Registration.AutoConnect' => true,
            // Allow SSO to connect with existing account via username/password.
            'Garden.Registration.AllowConnect' => true,
            // DEPRECATED. Whether or not roles are synchronized during SSO.
            'Garden.SSO.SyncRoles' => false,
            // Set to "register" to only sync roles during user registration.
            'Garden.SSO.SyncRolesBehavior' => '',
            // Whether to synchronize user info on subsequent user SSO's.
            'Garden.Registration.ConnectSynchronize' => true,
            // Whether SSO acts like the "remember me" checkbox was selected.
            'Garden.SSO.RememberMe' => true,
            // Affects validation when SSOing. Also affects the users presented during SSO conflicts.
            'Garden.Registration.NameUnique' => true,
            'Garden.Registration.EmailUnique' => true,
            // Whether or not to send a welcome email to SSO users. Almost always false.
            'Garden.Registration.SendConnectEmail' => false,
        ], null);

        // Test SSO as someone that isn't signed in yet.
        $this->session->end();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->tearDownTestTraits();
    }
    //endregion

    //region Test harnesses and helpers.
    /**
     * POST to `/entry/connect` and return the controller.
     *
     * This is the basic test harness for most tests. It uses Bessy to dispatch to `/entry/connect`. You give it one
     * of two things:
     *
     * 1. A handler that acts like an addon's `entry_connectData_handler()` method.
     * 2. An array representing an SSO user. Most tests can be done with this.
     *
     * Either of those things act like your SSO addon. In this way we are mocking an SSO addon.
     *
     * @param callable|array $handlerOrUser An event handler to handle the connect data event or an array representing the user.
     * @param array $body The post body.
     * @param string|null $subpath A custom sub path to place after `entry/connect`.
     * @param bool $throw Whether or not to throw an exception if any form errors occur.
     * @return \EntryController
     */
    protected function entryConnect($handlerOrUser, $body = [], string $subpath = self::PROVIDER_KEY, bool $throw = true): \EntryController {
        if (!empty($body)) {
            $body += [
                'Connect' => 'Vous vous connectez?', // needed for postback detection
            ];
        }

        if (is_callable($handlerOrUser)) {
            $handler = $handlerOrUser;
        } elseif (is_array($handlerOrUser)) {
            $handler = $this->basicConnectCallback($handlerOrUser);
        } else {
            throw new \InvalidArgumentException(__METHOD__.' expects $handlerOrUser to be a callable or an array.');
        }

        /** @var EventManager $events */
        $events = $this->container()->get(EventManager::class);
        try {
            $events->bind('base_connectData', $handler);

            if ($subpath) {
                $subpath = '/'.ltrim($subpath, '/');
            }

            $r = $this->bessy()->post('/entry/connect'.$subpath, $body, [TestDispatcher::OPT_THROW_FORM_ERRORS => $throw]);
            if (!($r instanceof \EntryController)) {
                throw new \InvalidArgumentException(__METHOD__.' did not return the EntryController: '.get_class($r));
            }
            return $r;
        } finally {
            $events->unbind('base_connectData', $handler);
        }
    }

    /**
     * Make a callback to pass to `entryConnect()`.
     *
     * This returns a callback that acts like an addon's `base_connectData` handler. All it does is pass the provided
     * user back to `entry/connect` for processing while setting additional required SSO meta data.
     *
     * @param array $user The user to SSO.
     * @return callable Returns the callback.
     */
    protected function basicConnectCallback(array $user): callable {
        $user += [
            'Provider' => self::PROVIDER_KEY,
            'ProviderName' => self::PROVIDER_KEY,
        ];

        /**
         * @param \EntryController $sender
         * @param array $args
         */
        $handler = function ($sender) use ($user) {
            foreach ($user as $key => $value) {
                if ($value !== null) {
                    $sender->Form->setFormValue($key, $value);
                }
            }
            $sender->setData('Verified', true);
        };

        return $handler;
    }

    /**
     * Assert that an SSO entry has a user in the database.
     *
     * @param string|array $uniqueID The unique SSO ID of the user or a user with a `UniqueID` key.
     * @param string $provider The name of the provider.
     * @return array Returns the user for further assertions.
     */
    protected function assertAuthentication($uniqueID, string $provider = self::PROVIDER_KEY): array {
        if (is_array($uniqueID)) {
            $uniqueID = $uniqueID['UniqueID'];
        }
        $auth = $this->userModel->getAuthentication($uniqueID, $provider);
        $this->assertNotFalse($auth, "The user doesn't have an authentication entry: $uniqueID, $provider");
        $user = $this->userModel->getID($auth['UserID'], DATASET_TYPE_ARRAY);
        $this->assertNotFalse($user, "The user was not found with the specified authentication: $uniqueID, $provider");
        return $user;
    }

    /**
     * Assert that an SSO entry was NOT found.
     *
     * This assertion is useful when checking against security vulnerabilities.
     *
     * @param string|array $uniqueID The unique SSO ID of the user or a user with a `UniqueID` key.
     * @param string $provider The name of the provider.
     */
    protected function assertNoAuthentication($uniqueID, string $provider = self::PROVIDER_KEY): void {
        if (is_array($uniqueID)) {
            $uniqueID = $uniqueID['UniqueID'];
        }
        $auth = $this->userModel->getAuthentication($uniqueID, $provider);
        $this->assertFalse($auth, "A user authentication record was found: $uniqueID, $provider");
    }

    /**
     * Assert that an SSO user array matches the database.
     *
     * @param array $ssoUser The SSO user to test. This is in the format passed to `entry/connect`.
     * @return array Returns the user for further assertions.
     */
    protected function assertSSOUser(array $ssoUser): array {
        $dbUser = $this->assertAuthentication($ssoUser['UniqueID']);
        unset($ssoUser['UniqueID'], $ssoUser['Provider'], $ssoUser['ProviderName']);
        $this->assertArraySubsetRecursive($ssoUser, $dbUser);
        return $dbUser;
    }

    /**
     * Create and SSO a dummy user and return them.
     *
     * The user's unique ID is their name when registered.
     *
     * @param array $overrides Overrides for the user record.
     * @return array
     */
    protected function ssoDummyUser(array $overrides = []): array {
        $user = $this->dummyUser($overrides);
        $userID = $this->userModel->connect($user['Name'], self::PROVIDER_KEY, $user);
        $this->assertNotEmpty($userID);

        $dbUser = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);

        $r = array_intersect_key($dbUser, $user);
        $r['UniqueID'] = $user['Name'];
        return $r;
    }
    //endregion

    //region Basic happy path tests.
    /**
     * A new user should be able to register through SSO.
     *
     * This is the most basic SSO scenario:
     *
     * 1. Someone has never been to your site.
     * 2. Their user information is minimal, but has no problems.
     *
     * The user should just register and have their SSO information saved.
     *
     * @return array Returns the user that registered so they can continue on their journey.
     */
    public function testMinimalSSORegistration(): array {
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__]);
        $r = $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser['UserID'], $this->session->UserID);

        $dbUser['UniqueID'] = $ssoUser['UniqueID'];
        return $dbUser;
    }

    /**
     * A returning user should be able to connect again and have their user information synchronized.
     *
     * Here is this scenario:
     *
     * 1. Someone has already SSO'd.
     * 2. They come back to the site and SSO again.
     *
     * They should be recognized by their `UniqueID` and their new user information should be updated.
     *
     * @param array $existingUser Pass a user that has already registered via SSO
     * @depends testMinimalSSORegistration
     */
    public function testMinimalSSOSync(array $existingUser): void {
        $newSSOInfo = $this->dummyUser();
        $newSSOInfo['UniqueID'] = $existingUser['UniqueID'];

        $r = $this->entryConnect($newSSOInfo);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($newSSOInfo);
        $this->assertEquals($dbUser['UserID'], $this->session->UserID);
    }

    /**
     * A returning user should be able to connect again without passing user information.
     *
     * This is a narrow edge case where users may get registered or connected through other means and then only SSO
     * with a unique ID.
     *
     * They should be recognized by their `UniqueID`.
     *
     * @param array $existingUser Pass a user that has already registered via SSO
     * @depends testMinimalSSORegistration
     */
    public function testMinimalSSONoSync(array $existingUser): void {
        $this->session->end();
        $r = $this->entryConnect(['UniqueID' => $existingUser['UniqueID']]);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertAuthentication($existingUser['UniqueID']);
        $this->assertEquals($dbUser['UserID'], $this->session->UserID);
    }

    /**
     * A user can connect to an existing user by email when `Garden.Registration.AutoConnect` is true.
     *
     * Auto-connect is one of Vanilla's most common SSO scenarios. It goes like his:
     *
     * 1. We've imported a tonne of users from some other platform.
     * 2. That platform didn't have SSO and/or the user is wiring Vanilla up to a new SSO provider.
     *
     * In this scenario we turn on auto-connect and then users can match up based on email the first time they SSO.
     * PLEASE NOTE: When this config setting is on the email addresses must be secured in some way, usually through a
     * verification process. Please ask if you are setting up SSO for someone.
     */
    public function testAutoConnect(): void {
        $this->config->set('Garden.Registration.AutoConnect', true);

        $importedUser = $this->insertDummyUser();
        $ssoUser = $this->dummyUser(['Email' => $importedUser['Email'], 'UniqueID' => __FUNCTION__]);

        $r = $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser['UserID'], $this->session->UserID);
    }

    /**
     * Roles can be passed through SSO, but must be allowed explicitly because of security concerns. There are two ways
     * to allow roles.
     *
     * 1. By having the provider mark itself as "Trusted" using `$sender->setData('Trusted', true)`.
     * 2. DEPRECATED. Using the `Garden.SSO.SyncRoles` configuration setting.
     */
    public function testSSORoles(): void {
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__, 'Roles' => [Bootstrap::ROLE_MOD]]);

        // First, connect the roles with a connection marked trusted.
        $this->entryConnect(function (\EntryController $sender) use ($ssoUser) {
            $this->basicConnectCallback($ssoUser)($sender);
            $sender->setData('Trusted', true);
        });
        $this->bessy()->assertNoFormErrors();
        $this->assertUserHasRoles($this->session->UserID, [Bootstrap::ROLE_MOD]);

        // Second, reconnect with the global config.
        $this->config->set('Garden.SSO.SyncRoles', true);
        $ssoUser['Roles'] = [Bootstrap::ROLE_ADMIN];
        $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();
        $this->assertUserHasRoles($this->session->UserID, [Bootstrap::ROLE_ADMIN]);
    }
    //endregion

    //region Missing required user information tests.

    /**
     * An SSO provider that doesn't provide an email address will need to prompt the user.
     *
     * This is the case with Twitter's SSO and many other social style SSO providers.
     */
    public function testMissingEmail(): void {
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__, 'Email' => null]);

        // The first hit to entry connect should return with a form for the user to fill out.
        $r = $this->entryConnect($ssoUser);
        $this->assertFalse($this->session->isValid());
        $html = $this->bessy()->getLastHtml();
        $html->assertContainsString('Add Info &amp; Create Account');
        $html->assertFormInput('Email');
        // TODO: Would be nice if this were prefilled, but it's not.
        $html->assertFormInput('ConnectName');

        // The user can now fill the form out.
        $body = ['Email' => __FUNCTION__.'@example.com'];
        $r2 = $this->entryConnect($ssoUser, $body);
        $this->bessy()->assertNoFormErrors();
        $this->assertSSOUser($body + $ssoUser);
    }

    //endregion

    //region User conflict and resolution tests.
    /**
     * If a user tries to SSO, but their email or username are already taken then they are presented with a list of
     * options to choose from. They must then change their information or connect to an existing user with username/password.
     *
     * @param bool $name Whether or not to generate a name conflict.
     * @param bool $email Whether or not to generate an email conflict.
     * @param callable|null $extraHandler An optional callback to call after the basic SSO handler takes place.
     * @return array Returns the dispatched controller to help other tests.
     */
    protected function entryConnectConflict(bool $name = true, bool $email = false, ?callable $extraHandler = null): array {
        // Insert an existing user for the conflict.
        $existingUser = $this->insertDummyUser();
        $this->assertTrue($name || $email, "You need to specify at least one conflict.");
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__.'%s']);
        if ($name) {
            $ssoUser['Name'] = $existingUser['Name'];
        }
        if ($email) {
            $this->config->set('Garden.Registration.AutoConnect', false);
            $ssoUser['Email'] = $existingUser['Email'];
        }

        if ($extraHandler !== null) {
            $handler = function (EntryController $entry) use ($ssoUser, $extraHandler) {
                $this->basicConnectCallback($ssoUser)($entry);
                $extraHandler($entry);
            };
        } else {
            $handler = $ssoUser;
        }

        // Clear out the connect button to simulate a first time postback.
        $r = $this->entryConnect($handler, [], self::PROVIDER_KEY, false);
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser['UniqueID']);

        $r->setData('@existingUser', $existingUser); // kluge to help other tests.
        $r->setData('@ssoUser', $ssoUser);
        return [$existingUser, $ssoUser, $r];
    }

    /**
     * If a user tries to SSO, but their email or username are already taken then they are presented with a list of
     * options to choose from. They must then change their information or connect to an existing user with username/password.
     *
     * @return \EntryController Returns the dispatched controller to help other tests.
     */
    public function testBasicSSONameConflict(): \EntryController {
        [$existingUser, $ssoUser, $r] = $this->entryConnectConflict();

        $existingUsers = array_column($r->data('ExistingUsers'), null, 'UserID');
        $this->assertArrayHasKey($existingUser['UserID'], $existingUsers, "Missing user with name match.");

        return $r;
    }

    /**
     * User's can also conflict on email addresses if auto connect is turned off.
     */
    public function testBasicSSOEmailConflict(): void {
        $this->config->set('Garden.Registration.AutoConnect', false);
        [$existingUser, $ssoUser, $r] = $this->entryConnectConflict(false, true);

        $existingUsers = array_column($r->data('ExistingUsers'), null, 'UserID');
        $this->assertArrayHasKey($existingUser['UserID'], $existingUsers, "Missing user with email match.");
    }

    /**
     * A user must enter the password of an existing user they wish to connect with.
     *
     * This test just makes sure the connection doesn't work if there is an invalid password. This is a very sensible
     * test to have since its failure would mean a critical security vulnerability.
     */
    public function testExistingConnectBadPassword(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="UserSelect"]');
        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="ConnectPassword"]');

        $this->expectExceptionMessage('The password you entered is incorrect.');
        $r = $this->entryConnect($ssoUser, [
            'UserSelect' => $entry->data('ExistingUsers.0.UserID'),
            'ConnectPassword' => 'Wrong Password',
        ]);
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser['UniqueID']);
    }

    /**
     * When a user enters the correct password for a user they are connecting with then they should connect.
     */
    public function testExistingConnectGoodPassword(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="UserSelect"]');
        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="ConnectPassword"]');
        $r = $this->entryConnect($ssoUser, [
            'UserSelect' => $existingUser['UserID'],
            'ConnectPassword' => $existingUser['Email'], // dummy users use email for password
        ]);
        $this->assertTrue($this->session->isValid());
        $this->assertAuthentication($ssoUser);
    }

    /**
     * The easiest way to resolve a conflict is to just have the user enter a new value for the conflicting field.
     */
    public function testChangeUsernameOnConflict(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="ConnectName"]');
        $r = $this->entryConnect($ssoUser, [
            'ConnectName' => $ssoUser['Name'].'-1',
        ]);
        $this->assertTrue($this->session->isValid());
        $dbUser = $this->assertAuthentication($ssoUser);
        $this->assertNotEquals($dbUser['UserID'], $existingUser['UserID']);
        $this->assertEquals($ssoUser['Name'].'-1', $dbUser['Name']);
    }

    /**
     * Test conflict resolution, but with changing an email address.
     *
     * If you look through the code here, you might notice that it is a fair amount more complicated than the username
     * case. This is legacy behavior, and I'm guessing that there was specific effort put into disallowing email changes
     * because email addresses are important to businesses.
     *
     * In this case, a user can only change their email address if the following happens:
     *
     * 1. The `'EmailVisible'` is posted and is present in the form. This would usually be set by the SSO addon.
     * 2. The `'Email'` form field is not overwritten by the SSO addon. This usually never happens so either there is
     *    specific log in some custom SSO addon that takes advantage of this or this is meant for SSO addons that don't
     *    supply an email address at all.
     *
     * This is wonky behavior for sure. I opted not to "fix" it because it has the effect of hiding the email address in
     * most cases.
     *
     * @see EntryControllerConnectTest::testChangeUsernameOnConflict()
     * @see https://github.com/vanilla/vanilla-cloud/issues/1032
     */
    public function testChangeEmailOnConflict(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict(false, true, function (EntryController $entry) {
            $entry->Form->setFormValue('EmailVisible', true);
        });
        $this->bessy()->getLastHtml()->assertCssSelectorExists('input[name="Email"]');

        $newEmail = $ssoUser['Email'].'.uk';
        unset($ssoUser['Email']); // kludge to simulate the SSO addon not overwriting the email address on the postback.
        $r = $this->entryConnect($ssoUser, [
            'Email' => $newEmail,
        ]);
        $this->assertTrue($this->session->isValid());
        $dbUser = $this->assertAuthentication($ssoUser);
        $this->assertNotEquals($dbUser['UserID'], $existingUser['UserID']);
        $this->assertEquals($newEmail, $dbUser['Email']);
    }

    /**
     * Make sure the user can't just submit another user's username or email on a conflict.
     */
    public function testNoChangeUsernameOnConflict(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $r = $this->entryConnect($ssoUser, [
            'ConnectName' => $existingUser['Name'],
        ], self::PROVIDER_KEY, false);
        $this->bessy()->assertFormErrorMessage('You are trying to connect with a username that is already assigned to a user on this forum.');
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * Make sure the user can't just submit another user's username or email on a conflict.
     */
    public function testNoChangeEmailOnConflict(): void {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict(false, true);

        $r = $this->entryConnect($ssoUser, [
            'ConnectEmail' => $existingUser['Email'],
        ], self::PROVIDER_KEY, false);
        $this->bessy()->assertFormErrorMessage('The email you entered is in use by another member.');
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * Users that have already connected before should not be hampered by conflicts.
     *
     * If a user is already linked through SSO and they SSO again with conflicts then the conflicting fields should just
     * be ignored and not updated rather than stop the SSO.
     */
    public function testAutoConflictResolution(): void {
        $ssoUser = $this->ssoDummyUser();
        $existingUser = $this->insertDummyUser();
        $conflictingSSOUser = $ssoUser;
        $conflictingSSOUser['Name'] = $existingUser['Name'];
        $conflictingSSOUser['Email'] = $existingUser['Email'];
        $conflictingSSOUser['Photo'] = 'https://example.com/avatar.jpg';

        $r = $this->entryConnect($conflictingSSOUser);
        $this->bessy()->assertNoFormErrors();
        $this->assertTrue($this->session->isValid());

        // The user's conflicting fields should not have updated.
        $dbUser = $this->assertAuthentication($conflictingSSOUser);
        $this->assertSame($conflictingSSOUser['Photo'], $dbUser['Photo']);
        $this->assertSame($ssoUser['Name'], $dbUser['Name']);
        $this->assertSame($ssoUser['Email'], $dbUser['Email']);
    }

    //endregion

    //region Basic SSO errors.

    /**
     * Every SSO addon must mark itself valid or the whole SSO should fail.
     *
     * We have this step to avoid security holes where no SSO addon applies, but the SSO is allowed anyway. That would
     * be very bad. So the idea is that SSO won't work until an addon tells it to work.
     */
    public function testSSONotVerified(): void {
        $this->expectExceptionMessage('The connection data has not been verified.');
        $this->entryConnect(function (EntryController $entry) {
            // Simulate a setting that some plucky developer might allow without realizing its repercussions.
            $entry->Form->setFormValue('Verified', true);
        });
    }

    /**
     * An SSO addon can add errors to the form to present to the user.
     *
     * This test also passes a user that would otherwise be fine for SSO to ensure that the SSO doesn't actually happen.
     */
    public function testSSOFormErrors(): void {
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__.'%s']);
        $this->entryConnect(function (EntryController $entry) use ($ssoUser) {
            $this->basicConnectCallback($ssoUser)($entry);
            $entry->Form->addError('Some error.');
        }, [], self::PROVIDER_KEY, false);
        $this->bessy()->assertFormErrorMessage('Some error.');
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * An SSO addon can throw a `Gdn_UserException` to halt SSO.
     *
     * This is a useful exception to know about when developing an SSO addon. It lets you do the following:
     *
     * 1. Do all your error checking first. Do it at the top of your `base_connectData` handler.
     * 2. If there are any weird errors that are going to make the rest of your logic difficult to write then throw an
     *    exception.
     * 3. This will leave you with a point in your code where all data is correct and has been checked. You can then
     *    use it without having to check it all the time.
     *
     * This would be for errors like the other server doesn't return a response or returns an error response to you.
     * Throw the exception to surface to the client, but don't worry about dealing with it if it's not your responsibility.
     */
    public function testSSOUserException(): void {
        debug(false);
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__.'%s']);
        $this->entryConnect(function (EntryController $entry) use ($ssoUser) {
            $this->basicConnectCallback($ssoUser)($entry);
            throw new \Gdn_UserException("Some exception.");
        }, [], self::PROVIDER_KEY, false);
        $this->bessy()->getLastHtml()->assertContainsString('Some exception.');
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * If the SSO addon throws anything other than a `Gdn_UserException` then a generic error page should be shown.
     *
     * This protects against some unknown exception in the process leaking potentially sensitive information.
     */
    public function testSSOPanicException(): void {
        debug(false);
        $ssoUser = $this->dummyUser(['UniqueID' => __FUNCTION__.'%s']);
        $this->entryConnect(function (EntryController $entry) use ($ssoUser) {
            $this->basicConnectCallback($ssoUser)($entry);
            throw new \Exception("Some sensitive exception.");
        }, [], self::PROVIDER_KEY, false);
        $this->bessy()->getLastHtml()->assertContainsString('There was an error fetching the connection data.');
        $this->assertStringNotContainsString('Some sensitive exception.', $this->bessy()->getLastHtml()->getRawHtml());
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    //endregion

    //region Uncommon config setting tests.
    /**
     * You can disable connecting to an existing user entirely with a config setting.
     *
     * In this case a user should not get a list of existing users nor be allowed to connect to an existing user with a
     * `UserSelect/ConnectPassword` combo.
     */
    public function testExistingConnectNotAllowed(): void {
        $this->config->set('Garden.Registration.AllowConnect', false);
        $this->config->set('Garden.Registration.AutoConnect', false);
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();
        $this->assertFalse($entry->data('AllowConnect'));
        $this->assertEmpty($entry->data('ExistingUsers'));

        $r = $this->entryConnect($ssoUser, [
            'UserSelect' => $existingUser['UserID'],
            'ConnectPassword' => $existingUser['Email'], // dummy users use email for password
        ], self::PROVIDER_KEY, false);
        $this->bessy()->assertFormErrorMessage('The site does not allow you connect with an existing user.');
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser['UniqueID']);
    }
    //endregion
}

<?php
/**
 * @noinspection PhpUnusedLocalVariableInspection
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 *
 */

namespace Vanilla\Dashboard\Tests\Controllers;

use EntryController;
use VanillaTests\Bootstrap;
use VanillaTests\Dashboard\EntryControllerConnectTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
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
class EntryControllerConnectTest extends SiteTestCase
{
    use EntryControllerConnectTestTrait;
    use UsersAndRolesApiTestTrait;

    protected const PROVIDER_KEY = "ec-test";

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
    public function testMinimalSSORegistration(): array
    {
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__]);
        $r = $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);

        $dbUser["UniqueID"] = $ssoUser["UniqueID"];
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
    public function testMinimalSSOSync(array $existingUser): void
    {
        $newSSOInfo = $this->dummyUser();
        $newSSOInfo["UniqueID"] = $existingUser["UniqueID"];

        $r = $this->entryConnect($newSSOInfo);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($newSSOInfo);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
    }

    /**
     * A returning user should be able to connect again and have their user information synchronized,
     * even if the name passed in is blank through configurations.
     *
     * Here is this scenario:
     *
     * 1. Someone has already SSO'd.
     * 2. Configurations changed, resulting in 'Name' sent being blank.
     * 3. They come back to the site and SSO again.
     *
     * They should be recognized by their `UniqueID` and their new user information should be updated, including roles should still be updated
     *
     * @param array $existingUser Pass a user that has already registered via SSO
     * @depends testMinimalSSORegistration
     */
    public function testSSOSyncWithoutName(array $existingUser): void
    {
        $newSSOInfo = $this->dummyUser(["Name" => "", "Roles" => VanillaTestCase::ROLE_ADMIN]);
        $newSSOInfo["UniqueID"] = $existingUser["UniqueID"];
        $this->config->set("Garden.SSO.SyncRoles", true);
        $this->assertCount(0, $this->userModel->getRoleIDs($this->session->UserID));
        $r = $this->entryConnect($newSSOInfo);
        $this->bessy()->assertNoFormErrors();
        unset($newSSOInfo["Name"]);
        unset($newSSOInfo["Roles"]);
        $dbUser = $this->assertSSOUser($newSSOInfo);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
        $this->assertUserHasRoles($this->session->UserID, [VanillaTestCase::ROLE_ADMIN]);
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
    public function testMinimalSSONoSync(array $existingUser): void
    {
        $this->config->set("Garden.Registration.AutoConnect", false);
        $this->session->end();
        $r = $this->entryConnect(["UniqueID" => $existingUser["UniqueID"]]);

        $dbUser = $this->assertAuthentication($existingUser["UniqueID"]);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
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
     * verification process. Please ask if you are sfetting up SSO for someone.
     */
    public function testAutoConnect(): void
    {
        $this->config->set("Garden.Registration.AutoConnect", true);

        $importedUser = $this->insertDummyUser();
        $ssoUser = $this->dummyUser(["Email" => $importedUser["Email"], "UniqueID" => __FUNCTION__]);

        $r = $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
    }

    /**
     * Data provider for testAutoConnectWithVerifiedTrue for testing different representations of Verified=true
     *
     * @return array
     */
    public function provideVerifiedTrueData(): array
    {
        return [
            "1 as substitute" => ["1", 1],
            "String `true` as substitute" => ["2", "true"],
            "Boolean `true`" => ["3", true],
        ];
    }

    /**
     * Test above with Verified parameter in the request.
     * @dataProvider provideVerifiedTrueData
     */
    public function testAutoConnectWithVerifiedTrue(string $suffix, $verified): void
    {
        $this->config->set("Garden.Registration.AutoConnect", true);

        $importedUser = $this->insertDummyUser();
        $ssoUser = $this->dummyUser([
            "Email" => $importedUser["Email"],
            "Verified" => $verified,
            "UniqueID" => __FUNCTION__ . "_$suffix",
        ]);

        $r = $this->entryConnect($ssoUser);
        $this->bessy()->assertNoFormErrors();

        $ssoUser["Verified"] = forceBool($ssoUser["Verified"], 0, 1, 0);
        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
    }

    /**
     * Data provider for testAutoConnectWithFailedVerify for testing different representations of Verified=false
     *
     * @return array
     */
    public function provideVerifiedFalseData(): array
    {
        return [
            "0 as substitute" => ["1", 0],
            "String `false` as substitute" => ["2", "false"],
            "Boolean `false`" => ["3", false],
        ];
    }

    /**
     * Test autoconnect, when email_verified is false.
     * @dataProvider provideVerifiedFalseData
     */
    public function testAutoConnectWithFailedVerify(string $suffix, $verified): void
    {
        $this->config->set("Garden.Registration.AutoConnect", true);

        $importedUser = $this->insertDummyUser();
        $ssoUser = $this->dummyUser([
            "Email" => $importedUser["Email"],
            "Verified" => $verified,
            "UniqueID" => __FUNCTION__ . "_$suffix",
        ]);

        $r = $this->entryConnect($ssoUser);
        $this->assertFalse($this->session->isValid());
        $html = $this->bessy()->getLastHtml();
        $html->assertContainsString("Add Info &amp; Create Account");
        $html->assertContainsString("You already have an account here");
        $html->assertFormInput("UserSelect");
        // TODO: Would be nice if this were prefilled, but it's not.
        $html->assertFormInput("ConnectPassword");
        $r = $this->entryConnect($ssoUser, [
            "UserSelect" => $importedUser["UserID"],
            "ConnectPassword" => $importedUser["Email"], // dummy users use email for password
        ]);
        $ssoUser["Verified"] = forceBool($ssoUser["Verified"], 0, 1, 0);
        $this->assertSSOUser($ssoUser, true);
    }

    /**
     * Roles can be passed through SSO, but must be allowed explicitly because of security concerns. There are two ways
     * to allow roles.
     *
     * 1. By having the provider mark itself as "Trusted" using `$sender->setData('Trusted', true)`.
     * 2. DEPRECATED. Using the `Garden.SSO.SyncRoles` configuration setting.
     */
    public function testSSORoles(): void
    {
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__, "Roles" => [Bootstrap::ROLE_MOD]]);

        // First, connect the roles with a connection marked trusted.
        $this->entryConnect(function (\EntryController $sender) use ($ssoUser) {
            $this->basicConnectCallback($ssoUser)($sender);
            $sender->setData("Trusted", true);
        });
        $this->bessy()->assertNoFormErrors();
        $this->assertUserHasRoles($this->session->UserID, [Bootstrap::ROLE_MOD]);

        // Second, reconnect with the global config.
        $this->config->set("Garden.SSO.SyncRoles", true);
        $ssoUser["Roles"] = [Bootstrap::ROLE_ADMIN];
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
    public function testMissingEmail(): void
    {
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__, "Email" => null]);

        $this->config->set("Garden.Registration.AutoConnect", false);
        // The first hit to entry connect should return with a form for the user to fill out.
        $r = $this->entryConnect($ssoUser);
        $this->assertFalse($this->session->isValid());
        $html = $this->bessy()->getLastHtml();
        $html->assertContainsString("Add Info &amp; Create Account");
        $html->assertFormInput("Email");
        // TODO: Would be nice if this were prefilled, but it's not.
        $html->assertFormInput("ConnectName");

        // The user can now fill the form out.
        $body = ["Email" => __FUNCTION__ . "@example.com"];
        $r2 = $this->entryConnect($ssoUser, $body);
        $this->bessy()->assertNoFormErrors();
        $this->assertSSOUser($body + $ssoUser);
    }

    /**
     * An SSO provider that doesn't provide an email address will need to prompt the user.
     * This process shouldn't allow to takeover another user's account.
     */
    public function testMissingEmailCantTakeoverAccount(): void
    {
        $existingUser = $this->insertDummyUser();
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__, "Email" => null]);

        $this->config->set("Garden.Registration.AutoConnect", false);
        // The first hit to entry connect should return with a form for the user to fill out.
        $r = $this->entryConnect($ssoUser);
        $this->assertFalse($this->session->isValid());
        $html = $this->bessy()->getLastHtml();
        $html->assertContainsString("Add Info &amp; Create Account");
        $html->assertFormInput("Email");
        $html->assertFormInput("ConnectName");

        // The user can now fill the form out.
        $body = ["Email" => $existingUser["Email"]];
        $this->expectExceptionMessage("The email you entered is in use by another member.");
        $r2 = $this->entryConnect($ssoUser, $body);
    }

    /**
     * This scenario tests the following:
     *
     * 1. SSO doesn't provide email or username.
     * 2. User doesn't provide anything and posts.
     * 3. User then provides a username and email and SSO's.
     */
    public function testNoEmailOrUsernameRoundTrip(): void
    {
        $this->config->set("Garden.Registration.AutoConnect", false);
        $ssoUser = ["UniqueID" => __FUNCTION__];

        // The initial connect page should present the user with email and username inputs.
        $r = $this->entryConnect($ssoUser);
        $this->assertNotSignedIn();

        // The user just hits submit. Boo.
        $html = $this->bessy()->getLastHtml();
        $body = $html->getFormValues();
        $r = $this->entryConnectNoThrow($ssoUser, $body);
        $this->bessy()->assertFormFieldError("Email");
        $this->bessy()->assertFormFieldError("ConnectName");

        // The user then submits a proper user now.
        $this->entryConnect($ssoUser, ["Email" => __FUNCTION__ . "@example.com", "ConnectName" => __FUNCTION__]);
        $this->assertSSOUser($ssoUser + ["Email" => __FUNCTION__ . "@example.com", "Name" => __FUNCTION__]);
    }

    //endregion

    //region User conflict and resolution tests.
    /**
     * If a user tries to SSO, but their email or username are already taken then they are presented with a list of
     * options to choose from. They must then change their information or connect to an existing user with username/password.
     *
     * @param bool|int $name Whether or not to generate a name conflict.
     * @param bool $email Whether or not to generate an email conflict.
     * @param callable|null $extraHandler An optional callback to call after the basic SSO handler takes place.
     * @return array Returns the dispatched controller to help other tests.
     */
    protected function entryConnectConflict($name = true, bool $email = false, ?callable $extraHandler = null): array
    {
        // Insert an existing user for the conflict.
        $existingUser = $this->insertDummyUser();
        $this->assertTrue($name || $email, "You need to specify at least one conflict.");
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__ . "%s"]);
        if ($name) {
            $ssoUser["Name"] = $existingUser["Name"];

            if ((int) $name > 1) {
                for ($i = 2; $i <= $name; $i++) {
                    $user = $this->insertDummyUser();
                    $this->userModel->setField($user["UserID"], $existingUser["Name"]);
                }
            }
        }
        if ($email) {
            $this->config->set("Garden.Registration.AutoConnect", false);
            $ssoUser["Email"] = $existingUser["Email"];
        }

        if ($extraHandler !== null) {
            $handler = function (EntryController $entry) use ($ssoUser, $extraHandler) {
                $this->basicConnectCallback($ssoUser)($entry);
                $extraHandler($entry);
            };
        } else {
            $handler = $ssoUser;
        }

        // Do a first time call to entry/connect.
        $r = $this->entryConnect($handler, [], self::PROVIDER_KEY, false);
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser["UniqueID"]);

        $r->setData("@existingUser", $existingUser); // kluge to help other tests.
        $r->setData("@ssoUser", $ssoUser);
        return [$existingUser, $ssoUser, $r];
    }

    /**
     * If a user tries to SSO, but their email or username are already taken then they are presented with a list of
     * options to choose from. They must then change their information or connect to an existing user with username/password.
     *
     * @return \EntryController Returns the dispatched controller to help other tests.
     */
    public function testBasicSSONameConflict(): \EntryController
    {
        [$existingUser, $ssoUser, $r] = $this->entryConnectConflict();

        $existingUsers = array_column($r->data("ExistingUsers"), null, "UserID");
        $this->assertArrayHasKey($existingUser["UserID"], $existingUsers, "Missing user with name match.");

        $userID = $this->bessy()
            ->getLastHtml()
            ->assertFormInput("UserSelect")
            ->getAttribute("value");
        $this->assertEquals($existingUser["UserID"], $userID);

        return $r;
    }

    /**
     * User's can also conflict on email addresses if auto connect is turned off.
     */
    public function testBasicSSOEmailConflict(): void
    {
        $this->config->set("Garden.Registration.AutoConnect", false);
        [$existingUser, $ssoUser, $r] = $this->entryConnectConflict(false, true);

        $existingUsers = array_column($r->data("ExistingUsers"), null, "UserID");
        $this->assertArrayHasKey($existingUser["UserID"], $existingUsers, "Missing user with email match.");

        $userID = $this->bessy()
            ->getLastHtml()
            ->assertFormInput("UserSelect")
            ->getAttribute("value");
        $this->assertEquals($existingUser["UserID"], $userID);
    }

    /**
     * A user must enter the password of an existing user they wish to connect with.
     *
     * This test just makes sure the connection doesn't work if there is an invalid password. This is a very sensible
     * test to have since its failure would mean a critical security vulnerability.
     */
    public function testExistingConnectBadPassword(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()
            ->getLastHtml()
            ->assertCssSelectorExists('input[name="UserSelect"]');
        $this->bessy()
            ->getLastHtml()
            ->assertCssSelectorExists('input[name="ConnectPassword"]');

        $this->expectExceptionMessage("The password you entered is incorrect.");
        $r = $this->entryConnect($ssoUser, [
            "UserSelect" => $entry->data("ExistingUsers.0.UserID"),
            "ConnectPassword" => "Wrong Password",
        ]);
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser["UniqueID"]);
    }

    /**
     * When a user enters the correct password for a user they are connecting with then they should connect.
     */
    public function testExistingConnectGoodPassword(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("UserSelect");
        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("ConnectPassword");
        $r = $this->entryConnect($ssoUser, [
            "UserSelect" => $existingUser["UserID"],
            "ConnectPassword" => $existingUser["Email"], // dummy users use email for password
        ]);
        $this->assertSSOUser($ssoUser, true);
    }

    /**
     * The easiest way to resolve a conflict is to just have the user enter a new value for the conflicting field.
     */
    public function testChangeUsernameOnConflict(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("ConnectName");
        $r = $this->entryConnect($ssoUser, [
            "ConnectName" => $ssoUser["Name"] . "-1",
        ]);
        $this->assertTrue($this->session->isValid());
        $dbUser = $this->assertAuthentication($ssoUser);
        $this->assertNotEquals($dbUser["UserID"], $existingUser["UserID"]);
        $this->assertEquals($ssoUser["Name"] . "-1", $dbUser["Name"]);
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
    public function testChangeEmailOnConflict(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict(false, true, function (EntryController $entry) {
            $entry->Form->setFormValue("EmailVisible", true);
        });
        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("Email");

        $newEmail = $ssoUser["Email"] . ".uk";
        unset($ssoUser["Email"]); // kludge to simulate the SSO addon not overwriting the email address on the postback.
        $r = $this->entryConnect($ssoUser, [
            "Email" => $newEmail,
        ]);
        $this->assertTrue($this->session->isValid());
        $dbUser = $this->assertAuthentication($ssoUser);
        $this->assertNotEquals($dbUser["UserID"], $existingUser["UserID"]);
        $this->assertEquals($newEmail, $dbUser["Email"]);
    }

    /**
     * Make sure the user can't just submit another user's username or email on a conflict.
     */
    public function testNoChangeUsernameOnConflict(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        $r = $this->entryConnect(
            $ssoUser,
            [
                "ConnectName" => $existingUser["Name"],
            ],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()->assertFormErrorMessage(
            "You are trying to connect with a username that is already assigned to a user on this forum."
        );
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * Make sure the user can't just submit another user's username or email on a conflict.
     */
    public function testNoChangeEmailOnConflict(): void
    {
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict(false, true);

        $r = $this->entryConnect(
            $ssoUser,
            [
                "ConnectEmail" => $existingUser["Email"],
            ],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()->assertFormErrorMessage("The email you entered is in use by another member.");
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * Make sure an exception is triggered when the SSO provider does not supply an email & autoconnect is enabled.
     */
    public function testPreventSSOAutoConnectOnAbsentEmail(): void
    {
        $this->config->set("Garden.Registration.AutoConnect", true);
        $existingUser = $this->insertDummyUser();

        // This SSO user does not have a provided email
        $ssoUser = [
            "UniqueID" => __FUNCTION__,
        ];

        $this->expectExceptionMessage("Unable to auto-connect because SSO did not provide an email address.");
        $r = $this->entryConnect($ssoUser, [], self::PROVIDER_KEY, false);

        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * Users that have already connected before should not be hampered by conflicts.
     *
     * If a user is already linked through SSO and they SSO again with conflicts then the conflicting fields should just
     * be ignored and not updated rather than stop the SSO.
     */
    public function testAutoConflictResolution(): void
    {
        $ssoUser = $this->ssoDummyUser();
        $existingUser = $this->insertDummyUser();
        $conflictingSSOUser = $ssoUser;
        $conflictingSSOUser["Name"] = $existingUser["Name"];
        $conflictingSSOUser["Email"] = $existingUser["Email"];
        $conflictingSSOUser["Photo"] = "https://example.com/avatar.jpg";

        $r = $this->entryConnect($conflictingSSOUser);
        $this->bessy()->assertNoFormErrors();
        $this->assertTrue($this->session->isValid());

        // The user's conflicting fields should not have updated.
        $dbUser = $this->assertAuthentication($conflictingSSOUser);
        $this->assertSame($conflictingSSOUser["Photo"], $dbUser["Photo"]);
        $this->assertSame($ssoUser["Name"], $dbUser["Name"]);
        $this->assertSame($ssoUser["Email"], $dbUser["Email"]);
    }

    //endregion

    //region custom profile fields on connect page.

    /**
     * A new user should be able to register through connect page and update the user meta table with custom profile fields.
     *
     * This is the scenario when we create the user with some additional info - custom profile fields.
     *
     * 1. Someone has never been to your site.
     * 2. Their user information is minimal, and you have configured some custom profile fields.
     *
     * The user should just register and have their SSO information and custom profile fields info saved.
     *
     */
    public function testConnectPageWithCustomProfileFields()
    {
        $profileFieldData = $this->createProfileFieldsWithPermission(["registrationOptions" => "required"]);

        //Connect button is not pressed yet, checking if our profile fields are there
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__]);
        $r = $this->entryConnect($ssoUser);

        $lastHTML = $this->bessy()->getLastHtml();
        $this->bessy()->assertNoFormErrors();
        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("Profile[{$profileFieldData["apiName"]}]");
        $this->bessy()->assertNoFormErrors();

        //now lets connect again with submit and send some values so $isPostBack is true
        $body = $lastHTML->getFormValues();
        $profileKey = "Profile[{$profileFieldData["apiName"]}]";
        if (isset($body[$profileKey])) {
            unset($body[$profileKey]);
            $body["Profile"][$profileFieldData["apiName"]] = "testValue";
        }

        $r = $this->entryConnect($ssoUser, $body);

        //we successfully created our user
        $dbUser = $this->assertSSOUser($ssoUser);
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);
        $this->assertTrue($this->session->isValid());

        //And we should have our custom profile field in user meta
        $userMetaModel = \Gdn::userMetaModel();
        $metaData = $userMetaModel->getUserMeta($dbUser["UserID"]);
        $this->assertArrayHasKey("Profile." . $profileFieldData["apiName"], $metaData);
        $this->assertEquals($metaData["Profile." . $profileFieldData["apiName"]], "testValue");
    }

    /**
     * A new user coming in for registration through sso should be able to register and update data through connect page
     * Any existing data for profile fields should be pre-populated and available for edit
     * Any hidden/internal fields shouldn't be shown on connect page
     * Internal/Hidden Fields available on SSO data should be captured and updated.
     */
    public function testConnectPageWithMultipleCustomProfileFields()
    {
        $options = [
            [
                "dataType" => "text",
                "registrationOptions" => "required",
            ],
            [
                "dataType" => "text",
                "visibility" => "internal",
                "registrationOptions" => "optional",
            ],
            [
                "dataType" => "text",
                "mutability" => "restricted",
                "registrationOptions" => "optional",
            ],
            [
                "dataType" => "text",
                "registrationOptions" => "hidden",
            ],
        ];
        //Create necessary profile fields
        for ($i = 0; $i < count($options); $i++) {
            $profileFieldData[] = $this->createProfileFieldsWithPermission($options[$i]);
        }
        $permitted_chars = "0123456789abcdefghijklmnopqrstuvwxyz";

        //Now create a new sso user having profile field data
        $uniqueID = uniqid("connect_");
        $ssodata = ["UniqueID" => $uniqueID];

        foreach ($profileFieldData as $key => $value) {
            $ssodata[$value["apiName"]] = substr(str_shuffle($permitted_chars), 0, 10);
        }
        $ssoUser = $this->dummyUser($ssodata);
        // First time coming in he will given a form with profile fields
        $r = $this->entryConnect($ssoUser);
        $formValues = $r->Form->formValues();
        $visibleFields = $notVisisbleFields = [];
        foreach ($profileFieldData as $key => $value) {
            if ($value["visibility"] != "internal" && $value["registrationOptions"] != "hidden") {
                $visibleFields[$value["apiName"]] = $ssodata[$value["apiName"]];
            } else {
                $notVisisbleFields[$value["apiName"]] = $ssodata[$value["apiName"]];
            }
        }
        $this->assertEquals($visibleFields, $formValues["Profile"]);

        $lastHTML = $this->bessy()->getLastHtml();
        $this->bessy()->assertNoFormErrors();

        //Verify visible profile fields  are pre-populated for modification
        foreach ($visibleFields as $apiKey => $val) {
            $lastHTML->assertFormInput("Profile[$apiKey]", $val);
        }

        //Verify internal and hidden fields are not shown on the form
        foreach ($notVisisbleFields as $apiKey => $val) {
            $lastHTML->assertNoFormInput("Profile[$apiKey]");
        }

        // We need to now verify that all the profile fields are being saved on the post call
        $body = $lastHTML->getFormValues();
        foreach ($body as $key => $value) {
            if (substr($key, 0, 7) == "Profile") {
                unset($body[$key]);
            }
        }
        $body["Profile"] = $formValues["Profile"];

        $r = $this->entryConnect($ssoUser, $body);

        //we successfully created our user
        $dbUser = $this->assertSSOUser(array_splice($ssoUser, 0, 3));
        // Verify all the profilefield values passed from sso got saved

        $userMetaModel = \Gdn::userMetaModel();
        $metaData = $userMetaModel->getUserMeta($dbUser["UserID"]);
        $this->assertCount(count($ssoUser), $metaData);
        foreach ($metaData as $key => $val) {
            $key = str_replace("Profile.", "", $key);
            $this->assertEquals($ssoUser[$key], $val);
        }
        $dbUser["Profile"] = $ssoUser;
        $dbUser["UniqueID"] = $uniqueID;
        return $dbUser;
    }
    /**
     * Test some edge case scenarios for connect
     */
    public function testConnectPageEdgeCases()
    {
        $options = [
            [
                "dataType" => "text",
                "formType" => "dropdown",
                "registrationOptions" => "optional",
                "dropdownOptions" => ["A", "B", "C"],
            ],
            [
                "dataType" => "text",
                "formType" => "dropdown",
                "visibility" => "internal",
                "registrationOptions" => "optional",
                "dropdownOptions" => ["1", "2", "3"],
            ],
            [
                "dataType" => "string[]",
                "formType" => "tokens",
                "registrationOptions" => "optional",
                "dropdownOptions" => ["Token-1", "Token-2", "Token-3"],
            ],
        ];
        for ($i = 0; $i < count($options); $i++) {
            $profileFieldData[] = $this->createProfileFieldsWithPermission($options[$i]);
        }
        //Now create a new sso user having profile field data
        $ssodata = ["UniqueID" => uniqid("connect_")];
        $ssodata[$profileFieldData[0]["apiName"]] = "B";
        $ssodata[$profileFieldData[2]["apiName"]] = ["Token-2", "Token-3"];
        $ssoUser = $this->dummyUser($ssodata);
        //the values should be selected for the users in the form
        $r = $this->entryConnect($ssoUser);
        $formValues = $r->Form->formValues();
        $this->assertArrayHasKey("Profile", $formValues);
        foreach ($formValues["Profile"] as $key => $value) {
            $this->assertEquals($ssoUser[$key], $value);
        }
        $lastHTML = $this->bessy()->getLastHtml();
        //Test that our fields are selected
        $lastHTML->assertFormDropdown("Profile[{$profileFieldData[0]["apiName"]}]", "B");
        $lastHTML->assertFormToken("Profile[{$profileFieldData[2]["apiName"]}]", ["Token-2", "Token-3"]);

        //if we get values that was not part of our options we shouldn't select them by default
        $ssoUser[$profileFieldData[0]["apiName"]] = "X";
        $ssoUser[$profileFieldData[2]["apiName"]] = ["Token-10"];
        $r = $this->entryConnect($ssoUser);
        $lastHTML = $this->bessy()->getLastHtml();
        $body = $lastHTML->getFormValues();
        $dropdownField = $lastHTML->assertFormDropdown("Profile[{$profileFieldData[0]["apiName"]}]", null);
        $this->assertEmpty($dropdownField->getAttribute("data-value"));
        $tokenField = $lastHTML->assertFormToken("Profile[{$profileFieldData[2]["apiName"]}]", null);
        $tokenAttributes = json_decode($tokenField->getAttribute("data-props"), true);
        $this->assertEmpty($tokenAttributes["initialValue"]);
    }

    /**
     * A new user should not be able to register through connect page as the validation for required custom profile field will fail.
     *
     */
    public function testConnectPageWithCustomProfileFieldsValidation()
    {
        $profileFieldData = $this->createProfileFieldsWithPermission([
            "registrationOptions" => "required",
            "apiName" => "myField",
        ]);

        //Connect button is not pressed yet, checking if our profile fields are there
        $ssoUser = $this->dummyUser([
            "UniqueID" => "testProfileFieldsUniqueID",
            "Name" => "testProfileFieldsUser",
            "Email" => "testProfileFieldsUser@example.com",
        ]);
        $r = $this->entryConnect($ssoUser);

        $body = $this->bessy()
            ->getLastHtml()
            ->getFormValues();
        $body["Profile"][$profileFieldData["apiName"]] = "";

        //should throw an error as we did not provide the required profile field
        $this->expectExceptionMessage("myField is required.");
        $r = $this->entryConnect($ssoUser, $body);
    }

    /**
     * The case when we have the matching names conflict, we still should render profile fields so the user can complete the registration
     */
    public function testConnectPageWithCustomProfileFieldsUsernameMatchConflict(): void
    {
        //create a profile field
        $profileFieldData = $this->createProfileFieldsWithPermission(["registrationOptions" => "optional"]);

        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();

        //name and profile field should be existing on the page
        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("ConnectName");
        $this->bessy()
            ->getLastHtml()
            ->assertFormInput("Profile[" . $profileFieldData["apiName"] . "]");
    }

    /**
     * A returning sso user should be able to connect again and have their user information synchronized.
     *
     * 1. Someone has already SSO'd.
     * 2. They come back to the site and SSO again.
     *
     * They should be recognized by their `UniqueID` and their new user information should be updated.
     *
     * @param array $existingUser Pass a user that has already registered via SSO
     * @depends testconnectPageWithMultipleCustomProfileFields
     */
    public function testSSOSyncWithProfileFields(array $existingUser): void
    {
        $newSSOInfo = $this->dummyUser();
        $newSSOInfo["UniqueID"] = $existingUser["UniqueID"];

        // We need to now modify the api data and verify if they are being updated
        $i = 1;
        foreach ($existingUser["Profile"] as $key => $data) {
            $newSSOInfo[$key] = "Updated" . $data;
            $i++;
            if ($i > 2) {
                break;
            }
        }

        // We can now connect so that new data can be updated and the user will be loggedIn.
        $r = $this->entryConnect($newSSOInfo);
        $this->bessy()->assertNoFormErrors();

        $dbUser = $this->assertSSOUser(array_slice($newSSOInfo, 0, 3));
        $this->assertEquals($dbUser["UserID"], $this->session->UserID);

        //We need to make sure our data got updated
        $userMetaModel = \Gdn::userMetaModel();
        $metaData = $userMetaModel->getUserMeta($dbUser["UserID"]);
        foreach (array_slice($newSSOInfo, -2, 2) as $key => $val) {
            $this->assertEquals($val, $metaData["Profile." . $key]);
        }
    }

    //endregion

    //region Basic SSO errors.
    /**
     * Every SSO addon must mark itself valid or the whole SSO should fail.
     *
     * We have this step to avoid security holes where no SSO addon applies, but the SSO is allowed anyway. That would
     * be very bad. So the idea is that SSO won't work until an addon tells it to work.
     */
    public function testSSONotVerified(): void
    {
        $this->expectExceptionMessage("The connection data has not been verified.");
        $this->entryConnect(function (EntryController $entry) {
            // Simulate a setting that some plucky developer might allow without realizing its repercussions.
            $entry->Form->setFormValue("Verified", true);
        });
    }

    /**
     * An SSO addon can add errors to the form to present to the user.
     *
     * This test also passes a user that would otherwise be fine for SSO to ensure that the SSO doesn't actually happen.
     */
    public function testSSOFormErrors(): void
    {
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__ . "%s"]);
        $this->entryConnect(
            function (EntryController $entry) use ($ssoUser) {
                $this->basicConnectCallback($ssoUser)($entry);
                $entry->Form->addError("Some error.");
            },
            [],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()->assertFormErrorMessage("Some error.");
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
    public function testSSOUserException(): void
    {
        debug(false);
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__ . "%s"]);
        $this->entryConnect(
            function (EntryController $entry) use ($ssoUser) {
                $this->basicConnectCallback($ssoUser)($entry);
                throw new \Gdn_UserException("Some exception.");
            },
            [],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()
            ->getLastHtml()
            ->assertContainsString("Some exception.");
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser);
    }

    /**
     * If the SSO addon throws anything other than a `Gdn_UserException` then a generic error page should be shown.
     *
     * This protects against some unknown exception in the process leaking potentially sensitive information.
     */
    public function testSSOPanicException(): void
    {
        debug(false);
        $ssoUser = $this->dummyUser(["UniqueID" => __FUNCTION__ . "%s"]);
        $this->entryConnect(
            function (EntryController $entry) use ($ssoUser) {
                $this->basicConnectCallback($ssoUser)($entry);
                throw new \Exception("Some sensitive exception.");
            },
            [],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()
            ->getLastHtml()
            ->assertContainsString("There was an error fetching the connection data.");
        $this->assertStringNotContainsString(
            "Some sensitive exception.",
            $this->bessy()
                ->getLastHtml()
                ->getRawHtml()
        );
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
    public function testExistingConnectNotAllowed(): void
    {
        $this->config->set("Garden.Registration.AllowConnect", false);
        $this->config->set("Garden.Registration.AutoConnect", false);
        [$existingUser, $ssoUser, $entry] = $this->entryConnectConflict();
        $this->assertFalse($entry->data("AllowConnect"));
        $this->assertEmpty($entry->data("ExistingUsers"));

        $r = $this->entryConnect(
            $ssoUser,
            [
                "UserSelect" => $existingUser["UserID"],
                "ConnectPassword" => $existingUser["Email"], // dummy users use email for password
            ],
            self::PROVIDER_KEY,
            false
        );
        $this->bessy()->assertFormErrorMessage("The site does not allow you connect with an existing user.");
        $this->assertFalse($this->session->isValid());
        $this->assertNoAuthentication($ssoUser["UniqueID"]);
    }
    //endregion
}

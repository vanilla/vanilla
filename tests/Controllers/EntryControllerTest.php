<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use AccessTokenModel;
use BanModel;
use Gdn;
use Gdn_CookieIdentity;
use League\Uri\Http;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `EntryController` class.
 *
 * These tests aren't exhaustive. If more tests are added then we may need to tweak this class to use the `SiteTestTrait`.
 */
class EntryControllerTest extends VanillaTestCase
{
    use SiteTestTrait, SetupTraitsTrait;

    /**
     * @var \EntryController
     */
    private $controller;

    private $userData;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpTestTraits();

        $this->controller = $this->container()->get(\EntryController::class);
        $this->controller->getImports();
        $this->controller->Request = $this->container()->get(\Gdn_Request::class);
        $this->controller->initialize();
        $this->userData = $this->insertDummyUser();
    }

    /**
     * Test if form fields are generated correctly according to profile fields.
     */
    public function testGenerateFormCustomProfileFields(): void
    {
        $this->runWithConfig(
            ["Garden.Registration.Method" => "Basic", ProfileFieldModel::CONFIG_FEATURE_FLAG => true],
            function () {
                //first create some profile fields with different formType/dataType
                $this->createProfileField(["apiName" => "field-test-textInput"]);
                $this->createProfileField([
                    "apiName" => "field-test-dropdown",
                    "label" => "field test dropdown",
                    "description" => "this is a test description for dropdown",
                    "formType" => "dropdown",
                    "dataType" => "number",
                    "dropdownOptions" => [
                        "0" => 0,
                        "1" => 1,
                    ],
                    "enabled" => true,
                ]);
                $this->createProfileField([
                    "apiName" => "field-test-checkbox",
                    "label" => "field test checkbox",
                    "dataType" => "boolean",
                    "formType" => "checkbox",
                    "enabled" => true,
                ]);

                $expected = '<li class="form-group"><label for="Form_Profilefield-test-textInput">profile field test</label>
<div class="Gloss">this is a test</div><input type="text" id="Form_Profilefield-test-textInput" name="Profile[field-test-textInput]" value="" class="InputBox" /></li><li class="form-group"><label for="Form_Profilefield-test-dropdown">field test dropdown</label>
<div class="Gloss">this is a test description for dropdown</div><select id="Form_Profilefield-test-dropdown" name="Profile[field-test-dropdown]" class="" data-value="">
<option value=""></option>
<option value="0">0</option>
<option value="1">1</option>
</select></li><li><div class="Gloss">this is a test</div><label for="Form_Profilefield-test-checkbox" class="CheckBoxLabel"><input type="hidden" name="Checkboxes[]" value="Profile[field-test-checkbox]" /><input type="checkbox" id="Form_Profilefield-test-checkbox" name="Profile[field-test-checkbox]" value="1" class="" /> field test checkbox</label></li>';

                $this->expectOutputString($expected);
                $this->controller->generateFormCustomProfileFields();
            }
        );
    }

    /**
     * Test a basic registration flow with custom profile fields, with a required field.
     */
    public function testRegisterBasicWithCustomProfileFields(): void
    {
        $this->runWithConfig(
            ["Garden.Registration.Method" => "Basic", ProfileFieldModel::CONFIG_FEATURE_FLAG => true],
            function () {
                //first create some profile fields
                $result = $this->createProfileField([
                    "apiName" => "fieldTextBox",
                    "label" => "field test textBox",
                    "registrationOptions" => "required",
                ]);

                $this->assertEquals(201, $result->getStatusCode());

                //first create some profile fields
                $result = $this->createProfileField([
                    "apiName" => "field-test-tokens",
                    "label" => "field test tokens",
                    "formType" => "tokens",
                    "dataType" => "string[]",
                    "dropdownOptions" => [
                        "0" => "apple",
                        "1" => "orange",
                    ],
                    "registrationOptions" => "optional",
                ]);

                $this->assertEquals(201, $result->getStatusCode());

                //We check if our custom field exists on the registration page.
                $registerPage = $this->bessy()->getHtml("/entry/register");
                $registerPage->assertCssSelectorExists("#Form_ProfilefieldTextBox");

                $formFields = [
                    "Email" => "new@user.com",
                    "Name" => "NewUserName",
                    "Profile" => [
                        "fieldTextBox" => "testValue",
                        "field-test-tokens" =>
                            '[{"value":"apple","label":"apple"}, {"value":"orange","label":"orange"}]',
                    ],
                    "Password" => "jXM>e!gL4#38cP3Z",
                    "PasswordMatch" => "jXM>e!gL4#38cP3Z",
                    "TermsOfService" => "1",
                    "Save" => "Save",
                ];

                //test if tokens array from values will be generated correctly from json
                $tokenValuesArr = $this->controller->convertTokenValueToArray(
                    $formFields["Profile"]["field-test-tokens"]
                );
                $this->assertIsArray($tokenValuesArr);
                $this->assertEquals("apple", $tokenValuesArr[0]);

                $registrationResults = $this->bessy()->post("/entry/register", $formFields);

                //success
                $this->assertIsObject($registrationResults);
                $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["fieldTextBox"]);
                $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["field-test-tokens"]);
                $this->assertNotEmpty($registrationResults->Data["UserID"]);

                //profile fields should be successfully saved in userMeta
                $userMetaData = \Gdn::userMetaModel()->getUserMeta($registrationResults->Data["UserID"]);
                $this->assertEquals("testValue", $userMetaData["Profile.fieldTextBox"]);
                $this->assertIsArray($userMetaData["Profile.field-test-tokens"]);
                $this->assertEquals("apple", $userMetaData["Profile.field-test-tokens"][0]);
                $this->assertEquals("orange", $userMetaData["Profile.field-test-tokens"][1]);

                // Trying to register providing an empty required field, this will fail.
                $formFields["Profile"]["fieldTextBox"] = "";
                $this->expectExceptionMessage("fieldTextBox is required");
                $validationFailResults = $this->bessy()->post("/entry/register", $formFields);
            }
        );
    }

    /**
     * Test a basic registration flow with custom profile fields having column name of user Table will get saved to userMeta without errors.
     */
    public function testRegisterBasicWithCustomProfileFieldsHavingUserFieldColumnNames(): void
    {
        \Gdn::sql()->truncate("profileField");
        $session = \Gdn::session();
        $session->start(self::$siteInfo["adminUserID"]);
        $this->runWithConfig(
            ["Garden.Registration.Method" => "Basic", ProfileFieldModel::CONFIG_FEATURE_FLAG => true],
            function () {
                //first create some profile fields having User table column names
                $profileFieldTitle = $this->createProfileField([
                    "apiName" => "Title",
                    "label" => "Title",
                    "registrationOptions" => "required",
                ]);
                $this->assertEquals(201, $profileFieldTitle->getStatusCode());
                $profileFieldDateOfBirth = $this->createProfileField([
                    "apiName" => "DateOfBirth",
                    "label" => "Date Of Birth",
                    "registrationOptions" => "optional",
                ]);
                $this->assertEquals(201, $profileFieldDateOfBirth->getStatusCode());

                // Do a registration
                $formFields = [
                    "Email" => "testuser@test.com",
                    "Name" => "TestUser",
                    "Profile" => ["DateOfBirth" => "Hello", "Title" => "Tester"],
                    "Password" => "2f3Rg1l@I#Hs",
                    "PasswordMatch" => "2f3Rg1l@I#Hs",
                    "TermsOfService" => "1",
                    "Save" => "Save",
                ];

                $registrationResults = $this->bessy()->post("/entry/register", $formFields);

                //make sure the registration is success and user is generated
                $this->assertIsObject($registrationResults);
                $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["Title"]);
                $this->assertNotEmpty($registrationResults->Form->_FormValues["Profile"]["DateOfBirth"]);
                $this->assertNotEmpty($registrationResults->Data["UserID"]);

                // Make sure the profile field data gets stored properly in UserMeta table
                $profileFieldModel = \Gdn::getContainer()->get(ProfileFieldModel::class);
                $updatedUserMeta = $profileFieldModel->getUserProfileFields($registrationResults->Data["UserID"]);

                $this->assertIsArray($updatedUserMeta);
                $this->assertEquals($formFields["Profile"]["DateOfBirth"], $updatedUserMeta["DateOfBirth"]);
                $this->assertEquals($formFields["Profile"]["Title"], $updatedUserMeta["Title"]);
            }
        );
    }

    public function testRegistrationWithHiddenOrInternalProfileFieldWillBeFiltered()
    {
        $session = \Gdn::session();
        $session->start(self::$siteInfo["adminUserID"]);
        $options = [
            [
                "apiName" => "publicField",
                "label" => "Public  Field",
            ],
            [
                "apiName" => "InternalField",
                "label" => "Internal Field",
                "visibility" => "internal",
            ],
            [
                "apiName" => "publicHiddenField",
                "label" => "Public Hidden Field",
                "registrationOptions" => ProfileFieldModel::REGISTRATION_HIDDEN,
            ],
        ];
        $this->runWithConfig(
            ["Garden.Registration.Method" => "Basic", ProfileFieldModel::CONFIG_FEATURE_FLAG => true],
            function () use ($options) {
                //first create some profile fields
                $profileFields = [];
                //create our profileFields
                foreach ($options as $option) {
                    $result = $this->createProfileField($option);
                    $this->assertEquals(201, $result->getStatusCode());
                    $profileFields[$option["apiName"]] = $result->getBody();
                }

                $registerPage = $this->bessy()->getHtml("/entry/register");

                $profileApiKeys = array_keys($profileFields);
                //Check if our public profile field exists on the registration page.
                $registerPage->assertFormInput("Profile[$profileApiKeys[0]]", null);
                //Make sure our internal/Hidden field don't show up in registration form
                $registerPage->assertNoFormInput("Profile[$profileApiKeys[1]]");
                $registerPage->assertNoFormInput("Profile[$profileApiKeys[2]]");

                $formFields = [
                    "Email" => "malUser@user.com",
                    "Name" => "MalUserName",
                    "Profile" => [
                        $profileApiKeys[0] => "publicField",
                        //trying to force populate value
                        $profileApiKeys[1] => "internalField",
                        $profileApiKeys[2] => "hiddenField",
                    ],
                    "Password" => "jXM>e!gL4#38cP3Z",
                    "PasswordMatch" => "jXM>e!gL4#38cP3Z",
                    "TermsOfService" => "1",
                    "Save" => "Save",
                ];

                $registrationResults = $this->bessy()->post("/entry/register", $formFields);

                //success
                $this->assertIsObject($registrationResults);
                $this->assertNotEmpty($registrationResults->Data["UserID"]);

                //profile fields should be successfully saved in userMeta
                $userMetaData = \Gdn::userMetaModel()->getUserMeta($registrationResults->Data["UserID"]);
                $this->assertArrayHasKey("Profile.$profileApiKeys[0]", $userMetaData);
                $this->assertEquals("publicField", $userMetaData["Profile.$profileApiKeys[0]"]);
                $this->assertArrayNotHasKey("Profile.$profileApiKeys[1]", $userMetaData);
                $this->assertArrayNotHasKey("Profile.$profileApiKeys[2]", $userMetaData);
            }
        );
    }
    /**
     * Create profile fields.
     *
     * @param array $options
     */
    public function createProfileField(array $options = [])
    {
        $initialData = [
            "apiName" => "profile-field-test",
            "label" => "profile field test",
            "description" => "this is a test",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "mutability" => "all",
            "displayOptions" => ["profiles" => true, "userCards" => true, "posts" => true],
            "registrationOptions" => "optional",
        ];

        return $this->api()->post("/profile-fields", array_merge($initialData, $options));
    }

    /**
     * Target URLs should be checked for safety and UX.
     *
     * @param string|false $url
     * @param string $expected
     * @dataProvider provideTargets
     */
    public function testTarget($url, string $expected): void
    {
        $expected = url($expected, true);
        $actual = $this->controller->target($url);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test Target as an empty string.
     */
    public function testEmptyTarget(): void
    {
        $expected = url("/", true);
        $this->controller->Request->setQuery(["target" => ""]);
        $actual = $this->controller->target(false);
        $this->assertSame($expected, $actual);
    }

    /**
     * The querystring and form should control the target.
     */
    public function testTargetFallback(): void
    {
        $target = url("/foo", true);
        $this->controller->Request->setQuery(["target" => $target]);

        $this->assertSame($target, $this->controller->target());

        $target2 = url("/bar", true);
        $this->controller->Form->setFormValue("Target", $target2);
        $this->assertSame($target2, $this->controller->target());
    }

    /**
     * Provide some sign out target tests.
     *
     * @return array
     */
    public function provideTargets(): array
    {
        $r = [
            ["/foo", "/foo"],
            ["entry/signin", "/"],
            ["entry/signout?foo=bar", "/"],
            ["/entry/autosignedout", "/"],
            ["/entry/autosignedout234", "/entry/autosignedout234"],
            ["https://danger.test/hack", "/"],
            [false, "/"],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test a basic registration flow.
     */
    public function testRegisterBasic(): void
    {
        $this->runWithConfig(["Garden.Registration.Method" => "Basic"], function () {
            $user = self::sprintfCounter([
                "Name" => "test%s",
                "Email" => "test%s@example.com",
                "Password" => __FUNCTION__,
                "PasswordMatch" => __FUNCTION__,
                "TermsOfService" => "1",
            ]);

            $r = $this->bessy()->post("/entry/register", $user);
            $welcome = $this->assertEmailSentTo($user["Email"]);
            // Clear userID and reload froom session cookie.
            /** @var Gdn_CookieIdentity $cookieIdentity */
            $cookieIdentity = Gdn::factory("Identity");
            $cookieIdentity->UserID = null;
            // The user has registered. Let's simulate clicking on the confirmation email.
            $emailUrl = Http::createFromString($welcome->template->getButtonUrl());
            $this->assertStringContainsString("/entry/emailconfirm", $emailUrl->getPath());

            parse_str($emailUrl->getQuery(), $query);
            $this->assertArraySubsetRecursive(
                [
                    "vn_medium" => "email",
                    "vn_campaign" => "welcome",
                    "vn_source" => "register",
                ],
                $query
            );

            $r2 = $this->bessy()->get($welcome->template->getButtonUrl(), [], []);
            $this->assertTrue($r2->data("EmailConfirmed"));
            $this->assertSame((int) $r->data("UserID"), \Gdn::session()->UserID);
        });
    }

    /**
     * Test a basic registration flow with Remember Me option checked.
     */
    public function testRegisterBasicRememberMe(): void
    {
        $this->runWithConfig(["Garden.Registration.Method" => "Basic"], function () {
            $user = self::sprintfCounter([
                "Name" => "test%s",
                "Email" => "test%s@example.com",
                "Password" => __FUNCTION__,
                "PasswordMatch" => __FUNCTION__,
                "TermsOfService" => "1",
                "RememberMe" => 1,
            ]);

            $r = $this->bessy()->post("/entry/register", $user);
            $welcome = $this->assertEmailSentTo($user["Email"]);
            // clear userID, and reload from session cookie.
            /** @var Gdn_CookieIdentity $cookieIdentity */
            $cookieIdentity = Gdn::factory("Identity");
            $cookieIdentity->UserID = null;
            // The user has registered. Let's simulate clicking on the confirmation email.
            $emailUrl = Http::createFromString($welcome->template->getButtonUrl());
            $this->assertStringContainsString("/entry/emailconfirm", $emailUrl->getPath());

            parse_str($emailUrl->getQuery(), $query);
            $this->assertArraySubsetRecursive(
                [
                    "vn_medium" => "email",
                    "vn_campaign" => "welcome",
                    "vn_source" => "register",
                ],
                $query
            );

            $r2 = $this->bessy()->get($welcome->template->getButtonUrl(), [], []);
            $this->assertTrue($r2->data("EmailConfirmed"));
            $this->assertSame((int) $r->data("UserID"), \Gdn::session()->UserID);
        });
    }

    /**
     * If account has been banned by a ban rule.
     */
    public function testBannedAutomaticSignin(): void
    {
        $postBody = ["Email" => $this->userData["Email"], "Password" => $this->userData["Email"], "RememberMe" => 1];

        $this->userData = $this->userModel->getID($this->userData["UserID"], DATASET_TYPE_ARRAY);
        $banned = val("Banned", $this->userData, 0);
        $userData = [
            "UserID" => $this->userData["UserID"],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_AUTOMATIC),
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t("This account has been banned."));
        $r = $this->bessy()->post("/entry/signin", $postBody);
    }

    /**
     * If account has been banned manually.
     */
    public function testBannedManualSignin(): void
    {
        $postBody = ["Email" => $this->userData["Email"], "Password" => $this->userData["Email"], "RememberMe" => 1];

        $this->userData = $this->userModel->getID($this->userData["UserID"], DATASET_TYPE_ARRAY);
        $banned = val("Banned", $this->userData, 0);
        $userData = [
            "UserID" => $this->userData["UserID"],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_MANUAL),
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t("This account has been banned."));
        $r = $this->bessy()->post("/entry/signin", $postBody);
    }

    /**
     * If account has been banned by the "Warnings and notes" plugin or similar.
     */
    public function testBannedWarningSignin(): void
    {
        $postBody = ["Email" => $this->userData["Email"], "Password" => $this->userData["Email"], "RememberMe" => 1];

        $this->userData = $this->userModel->getID($this->userData["UserID"], DATASET_TYPE_ARRAY);
        $banned = val("Banned", $this->userData, 0);
        $userData = [
            "UserID" => $this->userData["UserID"],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_WARNING),
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t("This account has been temporarily banned."));
        $r = $this->bessy()->post("/entry/signin", $postBody);
    }

    /**
     * Test that rejecting an application (when the registration method is "Approval") also invalidates that user's session.
     */
    public function testRejectedApplicantSessionExpires(): void
    {
        $this->runWithConfig(
            [
                "Garden.Registration.Method" => "Approval",
            ],
            function () {
                $user = self::sprintfCounter([
                    "Name" => "test%s",
                    "Email" => "test%s@example.com",
                    "Password" => __FUNCTION__,
                    "PasswordMatch" => __FUNCTION__,
                    "TermsOfService" => "1",
                    "DiscoveryText" => "test",
                ]);

                $r = $this->bessy()->post("/entry/register", $user);
                $userID = (int) $r->Data["UserID"];
                $userSession = $this->api()
                    ->get("/sessions", ["userID" => $userID])
                    ->getBody();
                $this->assertCount(1, $userSession);

                $this->api()->setUserID(self::$siteInfo["adminUserID"]);

                // The session should be deleted when the user is rejected.
                $this->api()->delete("/applicants/{$userID}");

                $sessionAfterRejection = $this->api()
                    ->get("/sessions", ["userID" => $userID])
                    ->getBody();

                $this->assertEmpty($sessionAfterRejection);
            }
        );
    }

    /**
     * Test that rejecting an application (when the registration method is "Approval") also invalidates that user's session.
     */
    public function testRejectedApplicantRememberMeSessionExpires(): void
    {
        $this->runWithConfig(
            [
                "Garden.Registration.Method" => "Approval",
            ],
            function () {
                $user = self::sprintfCounter([
                    "Name" => "test%s",
                    "Email" => "test%s@example.com",
                    "Password" => __FUNCTION__,
                    "PasswordMatch" => __FUNCTION__,
                    "TermsOfService" => "1",
                    "DiscoveryText" => "test",
                    "RememberMe" => 1,
                ]);

                $r = $this->bessy()->post("/entry/register", $user);
                $userID = (int) $r->Data["UserID"];
                // clear userID, and reload from session cookie.
                /** @var Gdn_CookieIdentity $cookieIdentity */
                $cookieIdentity = Gdn::factory("Identity");
                $cookieIdentity->UserID = null;
                Gdn::session()->start();

                $userSession = $this->api()
                    ->get("/sessions", ["userID" => $userID])
                    ->getBody();
                $this->assertCount(1, $userSession);

                $this->api()->setUserID(self::$siteInfo["adminUserID"]);

                // The session should be deleted when the user is rejected.
                $this->api()->delete("/applicants/{$userID}");

                $sessionAfterRejection = $this->api()
                    ->get("/sessions", ["userID" => $userID])
                    ->getBody();

                $this->assertEmpty($sessionAfterRejection);
            }
        );
    }

    /**
     * Test checkAccessToken().
     *
     * @param string $path
     * @param bool $valid
     * @dataProvider providePathData
     */
    public function testTokenAuthentication(string $path, bool $valid): void
    {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start([1]);
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        /** @var \AccessTokenModel $tokenModel */
        $tokenModel = $this->container()->get(\AccessTokenModel::class);
        $tokenModel->issue($userID);
        $accessToken = $tokenModel->getWhere(["UserID" => $userID])->firstRow(DATASET_TYPE_ARRAY);
        $signedToken = $tokenModel->signTokenRow($accessToken);
        $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $signedToken;
        $session->end();
        \Gdn::request()->setPath($path);
        /** @var \Gdn_Auth $auth */
        $auth = $this->container()->get(\Gdn_Auth::class);
        $auth->startAuthenticator();
        if ($valid) {
            $this->assertEquals($userID, \Gdn::session()->UserID);
        } else {
            $this->assertEquals(0, \Gdn::session()->UserID);
        }
    }

    /**
     * Test checkAccessToken() with oldSalt config present.
     *
     * @param string $path
     * @param bool $valid
     * @dataProvider providePathData
     */
    public function testTokenAuthenticationOldSalt(string $path, bool $valid): void
    {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start([1]);
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $accessToken = $this->runWithConfig(["Garden.Cookie.Salt" => "123"], function () use ($userID) {
            // Issue version 1 old style token.
            /** @var \AccessTokenModel $tokenModel */
            $tokenModel = $this->container()->get(\AccessTokenModel::class);
            $tokenModel->issue($userID);
            return $tokenModel->getWhere(["UserID" => $userID])->firstRow(DATASET_TYPE_ARRAY);
        });
        \Gdn::request()->setPath($path);
        //Run with configs after update to new Sald value.
        $this->runWithConfig(["Garden.Cookie.Salt" => "456", "Garden.Cookie.OldSalt" => "123"], function () use (
            $accessToken,
            $session
        ) {
            //After new release old tokens(version 1) should use oldSalt to sign and to verify signature.
            /** @var \AccessTokenModel $tokenModel */
            $tokenModel = new AccessTokenModel();
            $signedToken = $tokenModel->signTokenRow($accessToken);
            $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $signedToken;
            $session->end();
            /** @var \Gdn_Auth $auth */
            $auth = $this->container()->get(\Gdn_Auth::class);
            $auth->startAuthenticator();
        });
        if ($valid) {
            $this->assertEquals($userID, \Gdn::session()->UserID);
        } else {
            $this->assertEquals(0, \Gdn::session()->UserID);
        }
    }

    /**
     * Provide path data.
     *
     * @return array
     */
    public function providePathData(): array
    {
        return [
            "valid-path" => ["api/v2", true],
            "valid-path-subc" => ["subc/api/v2", true],
            "invalid-path" => ["/invalid", false],
            "invalid-path-subc" => ["/subc1/subc2/api/v2", false],
        ];
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\APIv0\TestDispatcher;

/**
 * Common functionality for tests that need extensive entry/connect SSO tests.
 */
trait EntryControllerConnectTestTrait
{
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
     * Run setup logic.
     */
    public function setUpEntryControllerConnectTest(): void
    {
        $this->createUserFixtures();

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
                "AssociationSecret" => self::PROVIDER_KEY,
                "Name" => self::PROVIDER_KEY,
                "Trusted" => true,
            ],
            ["checkExisting" => true]
        );

        // Save some default SSO config settings to make testing easier and document the settings!
        $this->config->saveToConfig([
            // Relax username validation so we don't have to worry during SSO testing.
            "Garden.User.ValidationRegexPattern" => "`.+`",
            // If you don't know what this is then you haven't been at Vanilla long.
            "Garden.Registration.AutoConnect" => true,
            // Allow SSO to connect with existing account via username/password.
            "Garden.Registration.AllowConnect" => true,
            // DEPRECATED. Whether or not roles are synchronized during SSO.
            "Garden.SSO.SyncRoles" => false,
            // Set to "register" to only sync roles during user registration.
            "Garden.SSO.SyncRolesBehavior" => "",
            // Whether to synchronize user info on subsequent user SSO's.
            "Garden.Registration.ConnectSynchronize" => true,
            // Whether SSO acts like the "remember me" checkbox was selected.
            "Garden.SSO.RememberMe" => true,
            // Affects validation when SSOing. Also affects the users presented during SSO conflicts.
            "Garden.Registration.NameUnique" => true,
            "Garden.Registration.EmailUnique" => true,
            // Whether or not to send a welcome email to SSO users. Almost always false.
            "Garden.Registration.SendConnectEmail" => false,
        ]);

        // Test SSO as someone that isn't signed in yet.
        $this->session->end();
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
    protected function entryConnect(
        $handlerOrUser,
        $body = [],
        string $subpath = self::PROVIDER_KEY,
        bool $throw = true
    ): \EntryController {
        if (!empty($body)) {
            if ($this->bessy()->hasLastHtml() && $throw) {
                $html = $this->bessy()->getLastHtml();

                // Make sure that everything posted in the body is also in the form from before.
                foreach ($body as $key => $value) {
                    //Special case for profile fields.
                    if ($key == "Profile" && is_array($value)) {
                        $fieldName = array_key_first($value);
                        $key = $key . "[" . $fieldName . "]";
                    }
                    $html->assertFormInput($key);
                }

                // This is a kludge for our constantly appearing password input.
                if (!$html->hasFormInput("UserSelect") && !array_key_exists("ConnectPassword", $body)) {
                    $html->assertNoFormInput("ConnectPassword");
                }
            }

            $body += [
                "Connect" => "Vous vous connectez?", // needed for postback detection
            ];
        }

        if (is_callable($handlerOrUser)) {
            $handler = $handlerOrUser;
        } elseif (is_array($handlerOrUser)) {
            $handler = $this->basicConnectCallback($handlerOrUser);
        } else {
            throw new \InvalidArgumentException(__METHOD__ . ' expects $handlerOrUser to be a callable or an array.');
        }

        /** @var EventManager $events */
        $events = $this->container()->get(EventManager::class);
        try {
            $events->bind("base_connectData", $handler);

            if ($subpath) {
                $subpath = "/" . ltrim($subpath, "/");
            }

            $r = $this->bessy()->post("/entry/connect" . $subpath, $body, [
                TestDispatcher::OPT_THROW_FORM_ERRORS => $throw,
            ]);
            if (!($r instanceof \EntryController)) {
                throw new \InvalidArgumentException(
                    __METHOD__ . " did not return the EntryController: " . get_class($r)
                );
            }
            return $r;
        } finally {
            $events->unbind("base_connectData", $handler);
        }
    }

    /**
     * A convenience version of `entryConnect()` that doesn't throw on form errors.
     *
     * @param callable|array $handlerOrUser
     * @param array $body
     * @param string $subpath
     * @return \EntryController
     */
    protected function entryConnectNoThrow(
        $handlerOrUser,
        $body = [],
        string $subpath = self::PROVIDER_KEY
    ): \EntryController {
        return $this->entryConnect($handlerOrUser, $body, $subpath, false);
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
    protected function basicConnectCallback(array $user): callable
    {
        $user += [
            "Provider" => self::PROVIDER_KEY,
            "ProviderName" => self::PROVIDER_KEY,
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
            $sender->setData("Verified", true);
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
    protected function assertAuthentication($uniqueID, string $provider = self::PROVIDER_KEY): array
    {
        if (is_array($uniqueID)) {
            $uniqueID = $uniqueID["UniqueID"];
        }
        $auth = $this->userModel->getAuthentication($uniqueID, $provider);
        $this->assertNotFalse($auth, "The user doesn't have an authentication entry: $uniqueID, $provider");
        $user = $this->userModel->getID($auth["UserID"], DATASET_TYPE_ARRAY);
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
    protected function assertNoAuthentication($uniqueID, string $provider = self::PROVIDER_KEY): void
    {
        if (is_array($uniqueID)) {
            $uniqueID = $uniqueID["UniqueID"];
        }
        $auth = $this->userModel->getAuthentication($uniqueID, $provider);
        $this->assertFalse($auth, "A user authentication record was found: $uniqueID, $provider");
    }

    /**
     * Assert that an SSO user array matches the database.
     *
     * @param array $ssoUser The SSO user to test. This is in the format passed to `entry/connect`.
     * @param bool $assertSession Also assert that the session has started with that user.
     * @return array Returns the user for further assertions.
     */
    protected function assertSSOUser(array $ssoUser, bool $assertSession = false): array
    {
        $dbUser = $this->assertAuthentication($ssoUser["UniqueID"]);
        unset($ssoUser["UniqueID"], $ssoUser["Provider"], $ssoUser["ProviderName"]);
        $this->assertArraySubsetRecursive($ssoUser, $dbUser);

        if ($assertSession) {
            $this->assertEquals($dbUser["UserID"], $this->session->UserID);
        }

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
    protected function ssoDummyUser(array $overrides = []): array
    {
        $user = $this->dummyUser($overrides);
        $userID = $this->userModel->connect($user["Name"], self::PROVIDER_KEY, $user);
        $this->assertNotEmpty($userID);

        $dbUser = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);

        $r = array_intersect_key($dbUser, $user);
        $r["UniqueID"] = $user["Name"];
        return $r;
    }

    /**
     * Simulate a basic SSO round trip with a user that has to correct some information.
     *
     * This test does the following:
     *
     * 1. Tries to SSO the user as is.
     * 2. Make sure entry/connect doesn't SSO, but returns a form.
     * 3. Tries the SSO again fixed with whatever is in `$body`.
     * 4. Maks sure the second round trip works.
     *
     * @param array $ssoUser The user provided by SSO.
     * @param array $postbackBody Post additional data long with the SSO. This can be used for simulating additional data in the
     * entry/connect form.
     * @return array Returns the current user from the database for further assertions.
     */
    public function assertSSORoundTrip(array $ssoUser, $postbackBody = []): array
    {
        $r = $this->entryConnect($ssoUser);
        $this->assertFalse(
            $this->session->isValid(),
            'The user SSO\'d in even though there should be something to fix.'
        );

        // Redo the connect with the terms selected.
        $r = $this->entryConnect($ssoUser, $postbackBody);
        return $this->assertSSOUser($ssoUser, true);
    }
    /**
     * Create profile fields with privileged user - admin, to pass the permission.
     *
     * @param array $params For profile fields to override defaults.
     * @return array
     */
    protected function createProfileFieldsWithPermission(array $params = []): array
    {
        $this->createUserFixtures();
        $this->session->start($this->adminID);
        $this->config->set(ProfileFieldModel::CONFIG_FEATURE_FLAG, true);
        $profileFieldData = $this->createProfileField($params);
        $this->session->end();
        return $profileFieldData;
    }
    //endregion
}

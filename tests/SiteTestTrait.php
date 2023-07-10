<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;
use Garden\Web\Exception\ResponseException;
use Garden\Web\Redirect;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Http\InternalClient;
use Vanilla\Models\AddonModel;
use Vanilla\Permissions;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\VariablesProviderInterface;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\UrlUtils;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Allow a class to test against
 */
trait SiteTestTrait
{
    use BootstrapTrait {
        setupBeforeClass as private bootstrapBeforeClass;
        teardownAfterClass as private bootstrapAfterClass;
    }

    /**
     * @var InternalClient
     */
    protected $api;

    /**
     * @var array
     */
    protected static $siteInfo;

    /**
     * @var array
     */
    private static $symLinkedAddons;

    /**
     * @var array The addons to install. Restored on teardownAfterClass();
     */
    protected static $addons = ["vanilla", "conversations", "stubcontent"];

    /** @var array $enabledLocales */
    protected static $enabledLocales = [];

    /** @var array */
    private $sessionBak;

    /**
     * @var int
     */
    protected $memberID;

    /**
     * @var int
     */
    protected $adminID;

    /**
     * @var int
     */
    protected $moderatorID;

    /**
     * @var array
     */
    protected $roles;

    /**
     * @var \UserModel
     */
    protected $userModel;

    /**
     * @var \RoleModel
     */
    protected $roleModel;

    /** @var bool */
    private $userFixturesCreated = false;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        // These applications must currently all be enabled at startup or things can get flaky.
        $addons = array_unique(array_merge(["dashboard", "conversations", "vanilla"], static::$addons));
        return $addons;
    }

    /**
     * Install the site.
     */
    public static function setupBeforeClass(): void
    {
        self::setupBeforeClassSiteTestTrait();
    }

    /**
     * Setup before each test.
     */
    public function setupSiteTestTrait(): void
    {
        $this->setUpBootstrap();
        $this->backupSession();

        // Clear out all notifications before each test.
        static::container()->call(function (\Gdn_SQLDriver $sql, \UserModel $userModel, \RoleModel $roleModel) {
            $this->resetTable("Activity");
            $this->userModel = $userModel;
            $this->roleModel = $roleModel;
        });
        $this->api = static::container()->getArgs(InternalClient::class, [
            static::container()->get("@baseUrl") . "/api/v2",
        ]);

        // Save some configs.
        \Gdn::config()->saveToConfig([
            "Garden.User.RateLimit" => 0,
        ]);
    }

    /**
     * Tear down before each test.
     */
    public function teardownSiteTest(): void
    {
        $this->restoreSession();
    }

    /**
     * Configure the container before addons are started.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        return;
    }

    /**
     * Setup the site. This is the full implementation for `setupBeforeClass()` for easier overriding.
     */
    protected static function setupBeforeClassSiteTestTrait(): void
    {
        static::bootstrapBeforeClass();

        $dic = self::$container;
        static::configureContainerBeforeStartup($dic);

        /* @var TestInstallModel $installer */
        $installer = $dic->get(TestInstallModel::class);

        $installer->uninstall();
        $result = $installer->install([
            "site" => ["title" => EventManager::classBasename(get_called_class())],
            "addons" => static::getAddons(),
        ]);

        self::preparelocales();

        $dic->call(function (\Gdn_Locale $locale) {
            $locale->set("en");
        });

        // Start Authenticators
        $dic->get("Authenticator")->startAuthenticator();
        $dic->get(\Gdn_Dispatcher::class)->start();

        self::$siteInfo = $result;
        if (static::shouldUseCaching()) {
            self::enableCaching();
        }
    }

    /**
     * @return bool
     */
    public static function shouldUseCaching(): bool
    {
        return true;
    }

    /**
     * Create locale directory and locale definitions.php
     */
    public static function preparelocales()
    {
        $enabledLocales = [];
        foreach (static::$enabledLocales as $localeKey => $locale) {
            $enabledLocales["test_$localeKey"] = $locale;
            $localeDir = PATH_ROOT . "/locales/$localeKey";
            if (!(file_exists($localeDir) && is_dir($localeDir))) {
                mkdir($localeDir);
            }
            $localeFile = $localeDir . "/definitions.php";
            if (!file_exists($localeFile)) {
                $handle = fopen($localeFile, "w");
                $localeDefinitions = <<<TEMPLATE
<?php

 \$LocaleInfo['$localeKey'] = array (
  'Locale' => '$locale',
  'Name' => '$locale / locale',
  'EnName' => '$locale Name',
  'Description' => 'Official $locale description',
  'Version' => '000',
  'Author' => 'Vanilla Community',
  'AuthorUrl' => 'https://www.transifex.com/projects/p/vanilla/language/$locale/',
  'License' => 'none',
  'PercentComplete' => 100,
  'NumComplete' => 0,
  'DenComplete' => 0,
  'Icon' => '$locale.svg',
);

TEMPLATE;
                fwrite($handle, $localeDefinitions);
                fclose($handle);
            }
        }
        if (!empty($enabledLocales)) {
            /** @var ConfigurationInterface $config */
            $config = self::container()->get(ConfigurationInterface::class);
            $config->set("EnabledLocales", $enabledLocales, true);
        }
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function teardownAfterClass(): void
    {
        static::bootstrapAfterClass();
    }

    /**
     * Back up the container's session.
     *
     * This is a good method to call in your `setUp` method.
     *
     * @throws \Exception Throws an exception if the session has already been backed up.
     */
    protected function backupSession()
    {
        if (!empty($this->sessionBak)) {
            throw new \Exception("Cannot backup the session over a previous backup.", 500);
        }

        /* @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);

        $this->sessionBak = [
            "userID" => $session->UserID,
            "user" => $session->User,
            "permissions" => clone $session->getPermissions(),
        ];
    }

    /**
     * Restore a backed up session.
     *
     * Call this method after a call to `backupSession()`.
     *
     * @throws \Exception Throws an exception if there isn't a session to restore.
     */
    protected function restoreSession()
    {
        if (empty($this->sessionBak)) {
            throw new \Exception("No session to restore.", 500);
        }

        /* @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);

        $session->UserID = $this->sessionBak["userID"];
        $session->User = $this->sessionBak["user"];

        // Hack to get past private property. Don't do outside of tests.
        $this->callOn(
            $session,
            function (Permissions $perms) {
                $this->permissions = $perms;
            },
            $this->sessionBak["permissions"]
        );
        $this->sessionBak = null;
    }

    /**
     * Create a few test users and set them on the class.
     *
     * @param ?string $sx The suffix to use for the usernames and email addresses.
     *
     */
    protected function createUserFixtures(?string $sx = null): void
    {
        // Create some users to help.
        if ($sx === null) {
            if ($this->userFixturesCreated) {
                return;
            } else {
                $this->userFixturesCreated = true;
            }
            $sx = round(microtime(true) * 1000) . mt_rand(1000, 9999);
        }

        $this->adminID = $this->createUserFixture(VanillaTestCase::ROLE_ADMIN, $sx);
        $this->moderatorID = $this->createUserFixture(VanillaTestCase::ROLE_MOD, $sx);
        $this->memberID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER, $sx);
    }

    /**
     * Create a test user with a given role.
     *
     * @param string $role
     * @param string|null $sx
     * @return int
     */
    protected function createUserFixture(string $role, string $sx = null): int
    {
        static $count = 1;
        if ($sx === null) {
            $sx = $count++;
        }

        $row = $this->api()->post("/users", [
            "name" => "$role$sx",
            "email" => "$role.$sx@example.com",
            "password" => "test15!AVeryS3cUR3pa55W0rd",
            "roleID" => [$this->roleID($role)],
        ]);
        return $row["userID"];
    }

    /**
     * Return every roles, keyed by name.
     *
     * @return array
     */
    protected function getRoles(): array
    {
        if ($this->roles === null) {
            $roles = array_column(
                static::container()
                    ->get(\Gdn_SQLDriver::class)
                    ->getWhere("Role", [])
                    ->resultArray(),
                "RoleID",
                "Name"
            );
            $this->roles = $roles;
        }
        return $this->roles;
    }

    /**
     * Assert that a notification was inserted.
     *
     * @param int $userID The user that is supposed to be notified.
     * @param array $where An additional where passed to the activity table.
     * @return array Returns the notification row for further inspection.
     */
    public function assertNotification(int $userID, array $where = []): array
    {
        /** @var \ActivityModel $model */
        $model = static::container()->get(\ActivityModel::class);
        $row = $model->getWhere(["NotifyUserID" => $userID] + $where)->firstRow(DATASET_TYPE_ARRAY);
        TestCase::assertIsArray($row, "Notification not found.");
        return $row;
    }

    /**
     * Lookup the role ID for a role name.
     *
     * @param string $name
     * @return int
     */
    protected function roleID(string $name): int
    {
        if (!isset($this->getRoles()[$name])) {
            throw new \Exception("Role not found: $name");
        }

        return $this->getRoles()[$name];
    }

    /**
     * Define a role and set it in the role cache.
     *
     * @param array $role
     * @return int
     */
    protected function defineRole(array $role): int
    {
        $roleID = $this->roleModel->define($role);
        if (!$roleID) {
            throw new \Exception("Role not defined: " . $this->roleModel->Validation->resultsText());
        }
        if ($this->roles !== null) {
            $this->roles[$role["Name"]] = $roleID;
        }
        return $roleID;
    }

    /**
     * Get the API client for internal requests.
     *
     * @return InternalClient Returns the API client.
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Insert a dummy user and return their row.
     *
     * By default, we use the email address as a password. This is to give the users all different passwords, but also
     * passwords that are easy to use for tests.
     *
     * @param array $overrides Overrides for the user row.
     * @return array
     */
    protected function insertDummyUser(array $overrides = []): array
    {
        $user = $this->dummyUser($overrides);
        $user += ["Password" => $user["Email"]];
        $userID = $this->userModel->register($user);
        ModelUtils::validationResultToValidationException($this->userModel, \Gdn::locale());
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);

        return $user;
    }

    /**
     * Make sure a user has a list of roles.
     *
     * @param int $userID The user to check.
     * @param array $roles The expected roles.
     */
    protected function assertUserHasRoles(int $userID, array $roles)
    {
        $userRoles = $this->userModel->getRoleIDs($userID);
        foreach ($roles as $role) {
            if (!is_numeric($role)) {
                $role = $this->roleID($role);
            }

            $this->assertContains($role, $userRoles);
        }
    }

    /**
     * Make sure a user does not have any roles in a list.
     *
     * @param int $userID
     * @param array $roles
     */
    protected function assertUserHasNotRoles(int $userID, array $roles)
    {
        $userRoles = $this->userModel->getRoleIDs($userID);
        foreach ($roles as $role) {
            if (!is_numeric($role)) {
                $role = $this->roleID($role);
            }

            $this->assertNotContains($role, $userRoles);
        }
    }

    /**
     * Make sure that there is no active session.
     *
     * @param string $message
     */
    public static function assertNotSignedIn($message = ""): void
    {
        TestCase::assertFalse(\Gdn::session()->isValid(), $message ?: "There shouldn't be a user signed in right now.");
    }

    /**
     * Assert that an API endpoint supports paging properly.
     *
     * @param string $url The URL to test.
     * @param int $limit The limit to use when paging. The endpoint must return at least one page of data.
     */
    public function assertApiPaging(string $url, int $limit): void
    {
        $url = UrlUtils::concatQuery($url, ["limit" => $limit]);
        $page = 0;
        do {
            $page++;
            $response = $this->api->get($url);

            $header = $response->getHeader(WebLinking::HEADER_NAME);
            TestCase::assertNotEmpty($header, "The paging headers were not in the API response.");

            $paging = WebLinking::parseLinkHeaders($header);
            $url = $paging["next"] ?? "";

            if (!empty($url)) {
                TestCase::assertCount(
                    $limit,
                    $response->getBody(),
                    "The API returned a row count different than the limit."
                );

                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                TestCase::assertSame(
                    $limit,
                    (int) $query["limit"],
                    "The limit in the next URL is not the same as the previous."
                );
                TestCase::assertSame($page + 1, (int) $query["page"], "The next page URL is not the current page + 1.");
            }
        } while (!empty($url));

        TestCase::assertNotSame(1, $page, "The paging test must go past page 1.");
    }

    /**
     * Enable our locale fixtures.
     */
    public static function enableLocaleFixtures(): void
    {
        $addonManager = self::container()->get(AddonManager::class);
        $addonModel = self::container()->get(AddonModel::class);
        $testLocaleAddon = new Addon("/tests/fixtures/locales/test");
        $addonManager->add($testLocaleAddon);
        $addonModel->enable($testLocaleAddon);

        $testLocaleAddon = new Addon("/tests/fixtures/locales/test-fr");
        $addonManager->add($testLocaleAddon);
        $addonModel->enable($testLocaleAddon);
    }

    /**
     * Assert that some callback creates a redirect.
     *
     * @param string $expectedPath The expected redirect path.
     * @param int $expectedCode The expected redirect status code.
     * @param callable $callback The callback to generate the redirect.
     */
    public function assertRedirectsTo(string $expectedPath, int $expectedCode, callable $callback)
    {
        $expectedPath = url($expectedPath);
        $caught = null;
        try {
            call_user_func($callback);
        } catch (ResponseException $e) {
            $caught = $e;
        }
        TestCase::assertInstanceOf(ResponseException::class, $caught, "Callback did not redirect.");
        $redirect = $caught->getResponse();
        TestCase::assertInstanceOf(Redirect::class, $redirect, "Callback did not redirect.");
        TestCase::assertEquals($expectedCode, $redirect->getStatus());
        TestCase::assertEquals($expectedPath, $redirect->getHeader("Location"));
    }

    /**
     * Run a callback with certain theme variables.
     *
     * @param array $variables The variables to apply.
     * @param callable $callback The callback to run.
     *
     * @return mixed The result of the callback.
     */
    public function runWithThemeVariables(array $variables, callable $callback)
    {
        $provider = new class ($variables) implements VariablesProviderInterface {
            /** @var array */
            private $variables;

            /**
             * Constructor.
             *
             * @param array $variables
             */
            public function __construct(array $variables)
            {
                $this->variables = $variables;
            }

            /**
             * @inheritDoc
             */
            public function getVariables(): array
            {
                return $this->variables;
            }
        };
        $themeService = $this->container()->get(ThemeService::class);
        $themeService->clearVariableProviders();
        try {
            $themeService->addVariableProvider($provider);
            $result = call_user_func($callback);
            return $result;
        } finally {
            $themeService->clearVariableProviders();
        }
    }
}

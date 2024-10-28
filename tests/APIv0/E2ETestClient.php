<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv0;

use Garden\Container\Container;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Gdn;
use League\Uri\Http;
use PDO;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Utility\UrlUtils;
use VanillaTests\TestDatabase;

/**
 * The API client for Vanilla's API version 0.
 */
class E2ETestClient extends HttpClient
{
    /**
     * @var string The API key for making calls to the special test helper script.
     */
    private static $apiKey;

    /**
     * @var array The current config from the install.
     */
    private $config;

    /**
     * @var array The user context to make requests with.
     */
    private $user;

    /** @var string|null */
    private ?string $dbName;

    /** @var string */
    private string $configPath;

    /** @var bool */
    private bool $useSystemTokenOnNextRequest = false;

    /**
     * Create site with selected slug, based on e2e-tests.vanilla.localhost
     *
     * @param string $siteSlug Site slug.
     * @param array $withAddons list of addon to enable.
     *
     * @return E2ETestClient
     * @throws \Exception exceptions from site creation.
     */
    public static function prepare(string $siteSlug, array $withAddons = []): E2ETestClient
    {
        $client = new E2ETestClient($siteSlug);
        // Cleanup from any previous tests.
        $client->uninstall();
        $client->install($siteSlug, false);

        // Setup the addons.
        foreach ($withAddons as $addon) {
            $client->enableAddon($addon);
            sleep(1);
        }

        return $client;
    }

    /**
     * APIv0 constructor.
     */
    public function __construct(?string $e2eSlug = null)
    {
        parent::__construct();
        $this->setThrowExceptions(true);
        $this->dbName = $e2eSlug;

        if ($e2eSlug) {
            $baseUrl = "http://e2e-tests.vanilla.localhost/$e2eSlug";
            $this->setBaseUrl($baseUrl);
            $this->configPath = PATH_CONF . "/e2e-tests.vanilla.localhost/$e2eSlug.php";
        } else {
            $this->setBaseUrl(getenv("TEST_BASEURL"));
            $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
            $configPath = PATH_CONF . "/$host.php";
            $this->configPath = $configPath;
        }
    }

    /**
     * Run the next request as the system user.
     *
     * @return E2ETestClient
     */
    public function runAsSystem(): E2ETestClient
    {
        $this->useSystemTokenOnNextRequest = true;
        return $this;
    }

    /**
     * Get the host of the database.
     *
     * @return string
     */
    public function getDbHost()
    {
        if (getenv("TEST_DB_HOST")) {
            $dbHost = getenv("TEST_DB_HOST");
        } else {
            $dbHost = "localhost";
        }
        if ($this->dbName) {
            $dbHost = "database";
        }
        return $dbHost;
    }

    /**
     * Get the name of the database for direct access.
     *
     * @return string Returns the name of the database.
     */
    public function getDbName()
    {
        if ($this->dbName) {
            return "test_" . $this->dbName;
        }
        $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);

        if (getenv("TEST_DB_NAME")) {
            $dbname = getenv("TEST_DB_NAME");
        } else {
            $dbname = preg_replace("`[^a-z]`i", "_", $host);
        }
        return $dbname;
    }

    /**
     * Get the username used to connect to the test database.
     *
     * @return string Returns a username.
     */
    public function getDbUser()
    {
        return getenv("TEST_DB_USER");
    }

    /**
     * Get the password used to connect to the test database.
     *
     * @return string Returns a password.
     */
    public function getDbPassword()
    {
        return getenv("TEST_DB_PASSWORD");
    }

    /**
     * Get a config value.
     *
     * @param string $key The dot-separated config key.
     * @param mixed $default The value to return if there is no config setting.
     * @return mixed Returns the config setting or {@link $default}.
     */
    public function getConfig($key, $default = null)
    {
        return $this->getGdnConfig()->get($key, $default);
    }

    /**
     * Get a Gdn_Configuration for the site.
     *
     * @return \Gdn_Configuration
     */
    private function getGdnConfig(): \Gdn_Configuration
    {
        $config = new \Gdn_Configuration();
        // Load default baseline Garden configurations.
        $config->load(PATH_CONF . "/config-defaults.php");

        // Load installation-specific configuration so that we know what apps are enabled.
        $config->load($this->getConfigPath(), "Configuration", true);
        return $config;
    }

    /**
     * Get the path to the config file for direct access.
     *
     * @return string Returns the path to the database.
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Get a connection to the database.
     *
     * @param bool $db Whether or not to add the db name to the DSN.
     * @return \PDO Returns a connection to the database.
     */
    public function getPDO($db = true)
    {
        static $pdo;

        if (!$pdo) {
            $options = [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            $dsn = "mysql:host=" . $this->getDbHost() . ";charset=utf8mb4";
            if ($db) {
                $dbname = $this->getDbName();
                $dsn .= ";dbname=$dbname";
            }

            $pdo = new PDO($dsn, $this->getDbUser(), $this->getDbPassword(), $options);
        }

        return $pdo;
    }

    /**
     * Generate a cookie for the user's transient key.
     *
     * @param int $userID
     * @param string $tk
     * @return string
     */
    private function generateTKCookie($userID, $tk)
    {
        $timestamp = time();

        $payload = "{$tk}:{$userID}:{$timestamp}";
        $signature = hash_hmac(
            $this->getConfig("Garden.Cookie.HashMethod", "md5"),
            $payload,
            $this->getConfig("Garden.Cookie.Salt")
        );

        return "{$payload}:{$signature}";
    }

    /**
     * {@inheritdoc}
     */
    public function handleErrorResponse(HttpResponse $response, $options = [])
    {
        $options += ["throw" => $this->throwExceptions];

        if ($options["throw"]) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $body["Exception"] ?? ($body["message"] ?? $response->getReasonPhrase());
                $message .= "\n" . json_encode($body, JSON_PRETTY_PRINT);
            }
            if (empty($message)) {
                $message = $response->getRawBody();
            }
            throw new \Exception($message . " (" . $response->getStatusCode() . ")", $response->getStatusCode());
        }
    }

    /**
     * Install Vanilla.
     *
     * @param string $title The title of the app.
     * @param bool $bootstrap If true bootstrap our container in the test suite.
     *
     * @throws \Exception Throws an exception if Vanilla fails to install.
     */
    public function install($title = "", bool $bootstrap = true)
    {
        $this->createDatabase();

        // Touch the config file because hhvm runs as root and we don't want the config file to have those permissions.
        $configPath = $this->getConfigPath();
        touch($configPath);
        chmod($configPath, 0777);
        $apiKey = sha1(random_bytes(16));
        $this->saveToConfig([
            "Garden.Errors.StackTrace" => true,
            "Garden.Embed.Allow" => true,
            "Test.APIKey" => $apiKey,
        ]);
        self::setAPIKey($apiKey);

        // Install Vanilla via cURL.
        $post = [
            "Database-dot-Host" => $this->getDbHost(),
            "Database-dot-Name" => $this->getDbName(),
            "Database-dot-User" => $this->getDbUser(),
            "Database-dot-Password" => $this->getDbPassword(),
            "Garden-dot-Title" => $title ?: "Vanilla Tests",
            "Email" => "test@example.com",
            "Name" => "test",
            "Password" => "test",
            "PasswordMatch" => "test",
        ];

        $r = $this->post("/dashboard/setup.json", $post);
        if (!$r["Installed"]) {
            throw new \Exception("Vanilla did not install.");
        }

        if ($bootstrap) {
            $this->bootstrap();
        }
    }

    /**
     * Enable an addon on the site.
     *
     * @param string $addonKey
     */
    public function enableAddon(string $addonKey): void
    {
        $this->runAsSystem()->patch("/api/v2/addons/{$addonKey}", [
            "enabled" => true,
        ]);
    }

    /**
     * Encode an array in a format suitable for a cookie header.
     *
     * @param array $array The cookie value array.
     * @return string Returns a string suitable to be passed to a cookie header.
     */
    public static function cookieEncode(array $array)
    {
        $pairs = [];
        foreach ($array as $key => $value) {
            $pairs[] = "$key=" . rawurlencode($value);
        }

        $result = implode("; ", $pairs);
        return $result;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception Throws an exception when Vanilla isn't properly configured.
     */
    public function createRequest(string $method, string $uri, $body, array $headers = [], array $options = [])
    {
        $request = parent::createRequest($method, $uri, $body, $headers, $options);

        if ($this->useSystemTokenOnNextRequest) {
            $index = 0;
            do {
                $systemToken = $this->getConfig(\AccessTokenModel::CONFIG_SYSTEM_TOKEN, null);
                if ($systemToken) {
                    break;
                }
                sleep(1);
                $index++;
            } while ($index < 5);
            if (!$systemToken) {
                throw new \Exception("Site did not have a system access token configured.");
            }

            $request->setHeader("Authorization", "Bearer {$systemToken}");
            $this->useSystemTokenOnNextRequest = false;
        }

        $uri = Http::createFromString($request->getUrl());

        if (str_ends_with($uri->getPath(), ".json")) {
            // What a horrific bug. I still would rather not need to configure nginx for a couple old tests
            // So instead we map the .json suffixes to just use the delivery type.
            // https://bugs.php.net/bug.php?id=61286
            $uri = $uri->withPath(str_replace(".json", "", $uri->getPath()));
            $uri = UrlUtils::replaceQuery($uri, [
                "DeliveryType" => DELIVERY_TYPE_DATA,
            ]);
            $request->setUrl((string) $uri);
        } else {
            if (is_array($body)) {
                $request->setHeader("content-type", "application/json");
            }
        }

        // Add the cookie of the calling user.
        if ($user = $this->getUser()) {
            $cookieName = $this->getConfig("Garden.Cookie.Name", "Vanilla");

            $cookieArray = [
                $cookieName => $this->vanillaCookieString($user["UserID"]),
                "{$cookieName}-tk" => $this->generateTKCookie($user["UserID"], $user["tk"]),
            ];

            $request->setHeader("Cookie", static::cookieEncode($cookieArray));

            if (!in_array($request->getMethod(), ["GET", "OPTIONS"])) {
                $body = $request->getBody();
                if (is_array($body)) {
                    if (!isset($body["TransientKey"])) {
                        $body["TransientKey"] = $user["tk"];
                        $request->setBody($body);
                    }
                } elseif (is_string($body)) {
                    if (strpos($body, "TransientKey") === false) {
                        if (!empty($body)) {
                            $body .= "&";
                        }
                        $body .= http_build_query(["TransientKey" => $user["tk"]]);
                        $request->setBody($body);
                    }
                }
            }
        }

        return $request;
    }

    /**
     * Generate a Vanilla compatible cookie string for a user.
     *
     * @param int $userID The ID of the user.
     * @param string $secret The secret to secure the user. This is the cookie salt. If you pass an empty string then
     * the current configured salt will be used.
     * @param string $algo The algorithm used to sign the cookie.
     * @return string Returns a string that can be used as a Vanilla session cookie.
     * @throws \Exception Throws an exception when there is no cookie salt configured.
     */
    public function vanillaCookieString($userID, $secret = "", $algo = "md5")
    {
        $expires = strtotime("+2 days");
        $keyData = "$userID-$expires";

        if (empty($secret)) {
            $secret = $this->getConfig("Garden.Cookie.Salt");
            if (empty($secret)) {
                // Throw a noisy exception because something is wrong.
                throw new \Exception("The cookie salt is empty.", 500);
            }
        }

        $keyHash = hash_hmac($algo, $keyData, $secret);
        $keyHashHash = hash_hmac($algo, $keyData, $keyHash);

        $cookieArray = [$keyData, $keyHashHash, time(), $userID, $expires];
        $cookieString = implode("|", $cookieArray);

        return $cookieString;
    }

    /**
     * Load the site's config and return it.
     *
     * This loads the config directly via filesystem access.
     *
     * @return array Returns the site's config.
     */
    public function loadConfigDirect()
    {
        $path = $this->getConfigPath();

        if (file_exists($path)) {
            $Configuration = [];
            require $path;
            return $Configuration;
        } else {
            return [];
        }
    }

    /**
     * Query the application's database.
     *
     * @param string $sql The SQL string to send.
     * @param array $params Any parameters to send with the SQL.
     * @param bool $returnStatement Whether or not to return the {@link \PDOStatement} associated with the query.
     * @return array|\PDOStatement
     * @throws \Exception Throws an exception if the query fails.
     */
    public function query($sql, array $params = [], $returnStatement = false)
    {
        $pdo = $this->getPDO();
        $stmt = $pdo->prepare($sql);

        $r = $stmt->execute($params);
        if ($r === false) {
            throw new \Exception($pdo->errorInfo(), $pdo->errorCode());
        }

        if ($returnStatement) {
            return $stmt;
        } else {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        }
    }

    /**
     * Query the application's database and return the first row of the result.
     *
     * @param string $sql The SQL string to send.
     * @param array $params Any parameters to send with the SQL.
     * @return array|null Returns the first row of the query or **null** if there is no data.
     * @throws \Exception Throws an exception if there was a problem executing the query.
     */
    public function queryOne($sql, $params = [])
    {
        $data = $this->query($sql, $params);
        if (empty($data)) {
            return null;
        } else {
            return reset($data);
        }
    }

    /**
     * Save some config values.
     *
     * @param array $values The values to save to the config.
     * @return array
     */
    public function saveToConfig(array $values)
    {
        $config = $this->getGdnConfig();
        $config->saveToConfig($values);
        $config->save();
        return $config->get(".");
    }

    /**
     * Delete the config.
     */
    public function deleteConfig()
    {
        if (file_exists($this->getConfigPath())) {
            unlink($this->getConfigPath());
        }

        if (file_exists($this->getConfigPath())) {
            throw new \Exception("Delete config did not work!");
        }
    }

    /**
     * Sign a user in to the application.
     *
     * @param string $username The username or email of the user.
     * @param string $password The password of the user.
     * @return HttpResponse Returns the response for signing in the user.
     */
    public function signInUser($username, $password)
    {
        $r = $this->post(
            "/entry/password.json",
            [
                "Email" => $username,
                "Password" => $password,
            ],
            [
                // So we pass the CSRF check.
                "X-Requested-With" => "XMLHttpRequest",
                "Referer" => $this->getBaseUrl(),
            ]
        );

        return $r;
    }

    /**
     * Uninstall Vanilla.
     *
     * @throws \Exception Throws an exception if the config file cannot be deleted.
     */
    public function uninstall()
    {
        $pdo = $this->getPDO(false);

        // Delete the config file.
        $this->deleteConfig();

        // Delete the database.
        $dbname = $this->getDbName();
        $pdo->query("drop database if exists `$dbname`");
    }

    /**
     * Get the apiKey.
     *
     * @return mixed Returns the apiKey.
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * Set the apiKey.
     *
     * @param mixed $apiKey
     * @return E2ETestClient Returns `$this` for fluent calls.
     */
    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    /**
     * Get the user to make API calls as.
     *
     * @return array Returns a user array.
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Query the system user.
     *
     * @param bool $throw Whether or not to throw an exception if the system user is not found.
     * @return array|null Returns the system user or **null** if they aren't found.
     * @throws \Exception Throws an exception if {@link $throw} is **true** and the system user isn't found.
     */
    public function querySystemUser($throw = false)
    {
        $user = $this->queryUser(["Admin" => 1], $throw);
        return $user;
    }

    /**
     * Query a user in the database.
     *
     * @param string|int $userKey The user ID or username of the user.
     * @param bool $throw Whether or not to throw an exception if the user isn't found.
     * @return array Returns the found user as an array.
     * @throws \Exception Throws an exception when the user isn't found and `$throw` is **true**.
     */
    public function queryUserKey($userKey, $throw = false)
    {
        if (is_numeric($userKey)) {
            $row = $this->queryUser(["UserID" => $userKey], $throw);
        } elseif (is_string($userKey)) {
            $row = $this->queryUser(["Name" => $userKey], $throw);
        }

        return $row;
    }

    /**
     * Query a use with a where array.
     *
     * @param array $where An array in the form **[field => value]**.
     * @param bool $throw Whether to throw an exception if the user isn't found.
     * @return array|null Returns the user array or null if no user is found.
     * @throws \Exception Throws an exception when the user isn't found.
     */
    public function queryUser($where, $throw = false)
    {
        // Build the where clause from the where array.
        $whereSql = [];
        $whereArgs = [];
        foreach ($where as $field => $value) {
            $whereSql[$field] = "$field = :$field";
            $whereArgs[":" . $field] = $value;
        }

        $sql = "select * from GDN_User where " . implode(" and ", $whereSql);
        $row = $this->queryOne($sql, $whereArgs);
        if (empty($row)) {
            if ($throw) {
                throw new \Exception("User not found.", 404);
            }
            return null;
        }

        $attributes = @unserialize($row["Attributes"]);
        $row["Attributes"] = $attributes;
        $row["tk"] = $attributes["TransientKey"] ?? "";

        return $row;
    }

    /**
     * Set the user used to make API calls.
     *
     * @param array|string|int $user Either an array user, an integer user ID, a string username, or null to unset the
     * current user.
     * @return E2ETestClient Returns `$this` for fluent calls.
     */
    public function setUser($user)
    {
        if ($user === null) {
            $this->user = null;
            return $this;
        }

        if (is_scalar($user)) {
            $user = $this->queryUserKey($user, true);
        }

        $partialUser = [
            "UserID" => $user["UserID"],
            "Name" => $user["Name"],
            "tk" => substr(md5(time()), 0, 16),
        ];

        $this->user = $partialUser;
        return $this;
    }

    public function createDatabase()
    {
        // Create the database for Vanilla.
        $pdo = $this->getPDO(false);
        $dbname = $this->getDbName();
        $pdo->query("create database `$dbname`");
        $pdo->query("use `$dbname`");
    }

    /**
     * Bootstrap some of the internal objects with this connection.
     */
    public function bootstrap()
    {
        $bootstrap = new \VanillaTests\Bootstrap("https://vanilla.test");
        $dic = new Container();
        $bootstrap->run($dic);

        // Make the core applications available.
        $adm = new AddonManager(
            [
                Addon::TYPE_ADDON => ["/applications", "/plugins"],
                Addon::TYPE_THEME => "/themes",
                Addon::TYPE_LOCALE => "/locales",
            ],
            PATH_ROOT . "/tests/cache/APIv0/vanilla-manager"
        );
        $adm->startAddonsByKey(["dashboard" => true, "vanilla" => true, "conversations" => true], Addon::TYPE_ADDON);
        spl_autoload_register([$adm, "autoload"]);

        $db = $dic->getArgs(TestDatabase::class, [$this->getPDO()]);

        $dic->setInstance(AddonManager::class, $adm)
            ->setInstance(Gdn::AliasDatabase, $db)
            ->setInstance(Gdn::AliasUserModel, new \UserModel());
    }

    public function terminate()
    {
        $dic = Gdn::getContainer();

        // Cleanup to prevent corruption of other tests
        $dic->setInstance(AddonManager::class, null);
        $dic->setInstance(Gdn::AliasDatabase, null);

        spl_autoload_unregister([Gdn::addonManager(), "autoload"]);
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Container\Container;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Psr\Container\ContainerInterface;
use PDO;

/**
 * Handles installing Vanilla.
 */
class InstallModel
{
    /** @var array  */
    protected static $DEFAULT_ADDONS = ["vanilla", "conversations", "stubcontent"];

    /** @var \Gdn_Configuration  */
    protected $config;

    /** @var AddonModel  */
    protected $addonModel;

    /** @var Container  */
    protected $container;

    /** @var \Gdn_Session  */
    protected $session;

    /**
     * InstallModel constructor.
     *
     * @param \Gdn_Configuration $config The configuration dependency used to load/save configuration information.
     * @param AddonModel $addonModel The addon model dependency used to enable installation addons.
     * @param Container $container The container used to create additional dependencies once they are enabled.
     */
    public function __construct(
        \Gdn_Configuration $config,
        AddonModel $addonModel,
        Container $container,
        \Gdn_Session $session
    ) {
        $this->config = $config;
        $this->addonModel = $addonModel;
        $this->container = $container;
        $this->session = $session;
    }

    /**
     * Generate a new update token.
     *
     * Note: This token doesn't actually do anything in and of itself. It just generates a cryptographically secure
     * token.
     *
     * @return string Returns a new random token.
     */
    public static function generateUpdateToken(): string
    {
        return sha1(random_bytes(40));
    }

    /**
     * Install Vanilla.
     *
     * @see InstallModel::getSchema()
     * @throws \Exception
     * @param array $data Database installation information.
     * @return array
     */
    public function install(array $data)
    {
        $data = $this->validate($data);

        // Set the initial config values.
        $config = [
            "Database.Host" => $data["database"]["host"],
            "Database.Name" => $data["database"]["name"],
            "Database.User" => $data["database"]["user"],
            "Database.Password" => $data["database"]["password"],

            "Garden.Title" => $data["site"]["title"],
            "Garden.Cookie.Salt" => $this->config->get("Garden.Cookie.Salt") ?: betterRandomString(32, "Aa0"),
            "Garden.Unsubscribe.Salt" => $this->config->get("Garden.Unsubscribe.Salt") ?: betterRandomString(32, "Aa0"),
            "Garden.Cookie.Domain" => "",
            "Garden.Registration.ConfirmEmail" => true,
            "Garden.Registration.SSOConfirmEmail" => false,
            "Garden.Email.SupportName" => $data["site"]["title"],
        ];
        $this->config->saveToConfig($config);
        /* @var \Gdn_Database $database */
        $database = $this->container->get(\Gdn_Database::class);
        $database->init();

        // Run the initial database structure for the dashboard.
        $dashboard = $this->addonModel->getAddonManager()->lookupAddon("dashboard");
        $this->addonModel->enable($dashboard, ["force" => true, "forceConfig" => false]);

        /* @var \UserModel $userModel */
        $userModel = $this->container->get(\UserModel::class);
        // Insert the admin user.
        $adminUserID = $userModel->saveAdminUser([
            "Name" => $data["admin"]["name"],
            "Email" => $data["admin"]["email"],
            "Password" => $data["admin"]["password"],
        ]);

        // Make sure that we install the addons as the admin user.
        if (!$this->session->isValid()) {
            $oldConfigValue = $this->config->get("Garden.Installed");
            $this->config->set("Garden.Installed", true);
            $this->session->start($adminUserID, false);
            $this->config->set("Garden.Installed", $oldConfigValue);
        }

        // Run through the addons.
        $data += ["addons" => static::$DEFAULT_ADDONS];

        foreach ($data["addons"] as $addonKey) {
            $addon = $this->addonModel->getAddonManager()->lookupAddon($addonKey);

            if ($addon === null) {
                throw new \InvalidArgumentException("Could not find addon with key: $addonKey", 404);
            }

            // TODO: Once we are using this addon model we can remove the force and tweak the config defaults.
            $this->addonModel->enable($addon, [
                "force" => true,
                // We already enabled the dashboard. No need to run its container configuration twice.
                "skipContainer" => $addon->getKey() === "dashboard",
            ]);
        }

        // Now that all of the addons are are enabled we should set the default roles.
        $this->addonModel->onAfterStructure();

        // Save the installation information.
        $this->config->saveToConfig([
            "Garden.Installed" => true,
            "Garden.Version" => APPLICATION_VERSION,
            "Garden.UpdateToken" => static::generateUpdateToken(),
        ]);

        $result = [
            "version" => APPLICATION_VERSION,
            "adminUserID" => empty($adminUserID) ? null : (int) $adminUserID,
        ];

        return $result;
    }

    /**
     * Validate the install environment.
     *
     * This includes PHP version, libraries, and permissions.
     *
     * @param array $data User data that can affect the validation.
     * @return array Returns the user data cleaned.
     * @throws ValidationException Throws an exception when the environment is not valid for installation.
     */
    public function validateEnvironment(array $data = [])
    {
        $validation = new Validation();

        if ($this->config->get("Garden.Installed")) {
            $validation->addError("", "Vanilla is already installed.", 409);
            throw new ValidationException($validation);
        }

        if (version_compare(phpversion(), ENVIRONMENT_PHP_VERSION) < 0) {
            $validation->addError("", "PHP {version} or higher is required.", ["version" => ENVIRONMENT_PHP_VERSION]);
        }

        if (!class_exists(\PDO::class)) {
            $validation->addError("", "{lib} is required.", ["lib" => \PDO::class]);
        } elseif (!in_array("mysql", \PDO::getAvailableDrivers())) {
            $validation->addError("", "{lib} is required.", ["lib" => "MySQL PDO"]);
        }

        if (!extension_loaded("gd")) {
            $validation->addError("", "{lib} is required.", ["lib" => "gd"]);
        }

        if (!extension_loaded("xml")) {
            $validation->addError("", "{lib} is required.", ["lib" => "libxml"]);
        }

        if (!$validation->isValid()) {
            throw new ValidationException($validation);
        }

        $dirs = [dirname($this->config->defaultPath()), PATH_UPLOADS, PATH_CACHE];
        foreach ($dirs as $dir) {
            if (!is_readable($dir) || !isWritable($dir)) {
                $validation->addError("", "{path} must be writable", ["path" => $dir]);
            }
        }

        if (
            file_exists(PATH_CACHE . "/Smarty/compile") &&
            (!is_readable(PATH_CACHE . "/Smarty/compile") || !isWritable(PATH_CACHE . "/Smarty/compile"))
        ) {
            $validation->addError("", "{path} must be writable", ["path" => PATH_CACHE . "/Smarty/compile"]);
        }

        if ($validation->isValidField("")) {
            $configPath = $this->config->defaultPath();

            // Make sure the config file is writable if it exists.
            if (file_exists($configPath) && (!is_readable($configPath) || !isWritable($configPath))) {
                $validation->addError("", "{path} must be writable", ["path" => $configPath]);
            }
        }

        // Make sure we can generate a strong random token.
        try {
            static::generateUpdateToken();
        } catch (\Exception $ex) {
            $validation->addError("", "Your system cannot generate a cryptographically strong random number.");
        }

        if (!$validation->isValid()) {
            throw new ValidationException($validation);
        }
        return $data;
    }

    /**
     * Validate the installation data.
     *
     * @param array $data The data to validate.
     * @return array Returns the validated data.
     * @throws ValidationException Throws an exception if the data isn't valid.
     */
    public function validate(array $data)
    {
        $validation = new Validation();

        $sch = $this->getSchema();

        // First validate the environment.
        try {
            $this->validateEnvironment($data);
        } catch (ValidationException $ex) {
            $validation->merge($ex->getValidation());
        }

        // Validate the schema.
        try {
            $data = $sch->validate($data);
        } catch (ValidationException $ex) {
            $validation->merge($ex->getValidation());
        }

        if (!$validation->isValid()) {
            throw new ValidationException($validation);
        }

        $this->validateDatabaseConnection($data["database"]);

        return $data;
    }

    /**
     * Check to see if the database connection information can connect to an actual database.
     *
     * @param array $dbInfo The database connection information.
     * @throws ValidationException Throws an exception if the database connection fails.
     */
    private function validateDatabaseConnection(array $dbInfo)
    {
        try {
            $this->createPDO($dbInfo);
        } catch (\PDOException $exception) {
            $validation = new Validation();
            switch ($exception->getCode()) {
                case 1044:
                    $validation->addError(
                        "",
                        "The database user you specified does not have permission to access the database. Have you created the database yet? The database reported: {dbMessage}.",
                        ["dbMessage" => strip_tags($exception->getMessage())]
                    );
                    break;
                case 1045:
                    $validation->addError(
                        "",
                        "Failed to connect to the database with the username and password you entered. Did you mistype them? The database reported: {dbMessage}.",
                        ["dbMessage" => strip_tags($exception->getMessage())]
                    );
                    break;
                case 1049:
                    $validation->addError(
                        "",
                        "It appears as though the database you specified does not exist yet. Have you created it yet? Did you mistype the name? The database reported: {dbMessage}.",
                        ["dbMessage" => strip_tags($exception->getMessage())]
                    );
                    break;
                case 2005:
                    $validation->addError(
                        "",
                        "Are you sure you've entered the correct database host name? Maybe you mistyped it? The database reported: {dbMessage}.",
                        ["dbMessage" => strip_tags($exception->getMessage())]
                    );
                    break;
                default:
                    $validation->addError(
                        "",
                        "The connection parameters you specified failed to open a connection to the database. The database reported: {dbMessage}.",
                        ["dbMessage" => strip_tags($exception->getMessage())]
                    );
                    break;
            }

            throw new ValidationException($validation);
        }
    }

    /**
     * Get a {@link PDO} DSN string from the database config.
     *
     * @param string[string] $dbInfo The database config.
     * @return string
     */
    private function getDatabaseDsn(array $dbInfo)
    {
        $dbname = empty($dbInfo["name"]) ? "" : "dbname={$dbInfo["name"]};";
        $r = "mysql:{$dbname}host=" . str_replace(":", ";port=", $dbInfo["host"]) . ";charset=utf8mb4";
        return $r;
    }

    /**
     * Get the schema for installation.
     *
     * @return Schema Returns the install schema.
     */
    public function getSchema()
    {
        $sch = Schema::parse([
            "database:o" => [
                "host:s" => "The host name of the database.",
                "name:s" => "The database name.",
                "user:s" => "The username used to access the database.",
                "password:s" => ["description" => "The database password.", "default" => ""],
            ],
            "site:o" => [
                "title:s" => 'Your application\'s title.',
            ],
            "admin:o" => [
                "email:s" => ["description" => "Admin email.", "format" => "email"],
                "name:s" => ["description" => "Admin username.", "minLength" => 3, "maxLength" => 20],
                "password:s" => ["description" => "Admin password.", "minLength" => 6],
            ],
            "addons:a?" => "s",
        ]);
        return $sch;
    }

    /**
     * Create a PDO connection to the database.
     *
     * @param array $info Database connection information.
     */
    protected function createPDO(array $info)
    {
        $pdo = new PDO($this->getDatabaseDsn($info), $info["user"], $info["password"], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
        ]);

        return $pdo;
    }
}

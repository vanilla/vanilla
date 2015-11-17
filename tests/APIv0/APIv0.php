<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv0;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use PDO;

class APIv0 extends HttpClient {
    const DB_USER = 'travis';
    const DB_PASSWORD = '';

    protected static $apiKey;

    public function __construct() {
        parent::__construct();
        $this
            ->setBaseUrl('http://vanilla.test:8080')
            ->setThrowExceptions(true);
    }

    /**
     * Get the name of the database for direct access.
     *
     * @return string Returns the name of the database.
     */
    public function getDbName() {
        $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
        $dbname = preg_replace('`[^a-z]`i', '_', $host);
        return $dbname;
    }

    /**
     * Get the path to the config file for direct access.
     *
     * @return string Returns the path to the database.
     */
    public function getConfigPath() {
        $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
        $path = PATH_ROOT."/conf/$host.php";
        return $path;
    }

    /**
     * Get a connection to the database.
     *
     * $return \PDO Returns a connection to the database.
     */
    public function getPDO() {
        static $pdo;

        if (!$pdo) {
            $options = [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND  => "set names 'utf8'"
            ];
            $pdo = new PDO("mysql:host=localhost", self::DB_USER, self::DB_PASSWORD, $options);

            $dbname = $this->getDbName();
            $r = $pdo->query("show databases like '$dbname'", PDO::FETCH_COLUMN, 0);
            $dbnames = $r->fetchColumn(0);

            if (!empty($dbnames)) {
                $pdo->query("use `$dbname`");
            }
        }

        return $pdo;
    }

    /**
     * @inheritdoc
     */
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $this->val(
                    'Exception',
                    $body,
                    $this->val('message', $body, $response->getReasonPhrase())
                );
            } else {
                $message = $response->getRawBody();
            }
            throw new \Exception($message.' ('.$response->getStatusCode().')', $response->getStatusCode());
        }
    }

    /**
     * Install Vanilla
     */
    public function install($title = '') {
        // Create the database for Vanilla.
        $pdo = $this->getPDO();
        $dbname = $this->getDbName();
        $pdo->query("create database `$dbname`");
        $pdo->query("use `$dbname`");

        // Touch the config file because hhvm runs as root and we don't want the config file to have those permissions.
        $configPath = $this->getConfigPath();
        touch($configPath);
        chmod($configPath, 0777);
        $apiKey = sha1(openssl_random_pseudo_bytes(16));
        $this->saveToConfigDirect(['Test.APIKey' => $apiKey]);
        self::setAPIKey($apiKey);


//        $dir = dirname($configPath);
//        passthru("ls -lah $dir");

        // Install Vanilla via cURL.
        $post = [
            'Database-dot-Host' => 'localhost',
            'Database-dot-Name' => $this->getDbName(),
            'Database-dot-User' => self::DB_USER,
            'Database-dot-Password' => self::DB_PASSWORD,
            'Garden-dot-Title' => $title ?: 'Vanilla Tests',
            'Email' => 'travis@example.com',
            'Name' => 'travis',
            'Password' => 'travis',
            'PasswordMatch' => 'travis'
        ];

        $r = $this->post('/dashboard/setup.json', $post);

        if (!$r['Installed']) {
            throw new \Exception("Vanilla did not install");
        }
    }

    /**
     * Load the site's config and return it.
     *
     * This loads the config directly via filesystem access.
     *
     * @return array Returns the site's config.
     */
    public function loadConfigDirect() {
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
     * Save some config values via API.
     *
     * This method saves config values via a back-door endpoint copied to cgi-bin.
     * This is necessary because HHVM runs as root and takes over the config file and so it can only be edited in an
     * API context.
     *
     * @param array $values The values to save.
     */
    public function saveToConfig(array $values) {
        $r = $this->post(
            '/cgi-bin/saveconfig.php',
            $values,
            [
                'Content-Type: application/json;charset=utf-8',
                'Authorization: token '.self::getApiKey()
            ]
        );

        $path = $this->getConfigPath();
    }

    /**
     * Save some config values.
     *
     * This saves the values directly via filesystem access.
     *
     * @param array $values An array of config keys and values where the keys are a dot-seperated array.
     */
    public function saveToConfigDirect(array $values) {
        $config = $this->loadConfigDirect();
        foreach ($values as $key => $value) {
            setvalr($key, $config, $value);
        }

        $path = $this->getConfigPath();

        $dir = dirname($path);

        $str = "<?php if (!defined('APPLICATION')) exit();\n\n".
            '$Configuration = '.var_export($config, true).";\n";
        $r = file_put_contents($path, $str);
    }

    /**
     * Sign a user in to the application.
     *
     * @param string $username The username or email of the user.
     * @param string $password The password of the user.
     */
    public function signInUser($username, $password) {
        $r = $this->post(
            '/entry/password.json',
            ['Email' => $username, 'Password' => $password]
        );

        return $r;
    }


    public function uninstall() {
        $pdo = $this->getPDO();

        // Delete the config file.
        $configPath = $this->getConfigPath();
        if (file_exists($configPath)) {
            $r = unlink($configPath);
            if (!$r) {
                throw new \Exception("Could not delete config file: $configPath", 500);
            }
        }

        // Delete the database.
        $dbname = $this->getDbName();
        $pdo->query("drop database if exists `$dbname`");
    }

    /**
     * Get the apiKey.
     *
     * @return mixed Returns the apiKey.
     */
    public static function getApiKey() {
        return self::$apiKey;
    }

    /**
     * Set the apiKey.
     *
     * @param mixed $apiKey
     * @return APIv0 Returns `$this` for fluent calls.
     */
    public static function setApiKey($apiKey) {
        self::$apiKey = $apiKey;
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use Interop\Container\ContainerInterface;
use Vanilla\Models\AddonModel;
use Vanilla\Models\InstallModel;

/**
 * A Vanilla installer that handles uninstalling.
 */
class TestInstallModel extends InstallModel {
    /**
     * @var string The URL of the site.
     */
    private $baseUrl;

    /**
     * @var string The database name.
     */
    private $dbName;

    /**
     * {@inheritdoc}
     */
    public function __construct(\Gdn_Configuration $config, AddonModel $addonModel, ContainerInterface $container) {
        parent::__construct($config, $addonModel, $container);
        $this->setBaseUrl($_ENV['baseurl']);

        $this->config->Data = [];
        $this->config->load(PATH_ROOT.'/conf/config-defaults.php');
        $this->config->load($this->getConfigPath(), 'Configuration', true);
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
     * Get the base URL of the site.
     *
     * @return mixed Returns the baseUrl.
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * Set the base URL of the site.
     *
     * @param mixed $baseUrl The new URL.
     * @return $this
     */
    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->config->defaultPath($this->getConfigPath());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function install(array $data) {
        $data = array_replace_recursive([
            'database' => $this->getDbInfo(),
            'site' => [
                'title' => __CLASS__
            ],
            'admin' => [
                'email' => 'travis@example.com',
                'name' => 'travis',
                'password' => 'travis'
            ]
        ], $data);

        $this->createDatabase($data['database']);

        return parent::install($data);
    }

    /**
     * Get an array with database connection information.
     *
     * @return array Returns a database connection information array.
     */
    private function getDbInfo() {
        return [
            'host' => $this->getDbHost(),
            'name' => $this->getDbName(),
            'user' => $this->getDbUser(),
            'password' => $this->getDbPassword()
        ];
    }

    /**
     * Get the database host.
     *
     * @return string
     */
    public function getDbHost() {
        $host = isset($_ENV['dbhost']) ? $_ENV['dbhost'] : 'localhost';
        return $host;
    }

    /**
     * Get the dbName.
     *
     * @return mixed Returns the dbName.
     */
    public function getDbName() {
        if (empty($this->dbName)) {
            $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);

            if (isset($_ENV['dbname'])) {
                $dbname = $_ENV['dbname'];
            } else {
                $dbname = preg_replace('`[^a-z]`i', '_', $host);
            }
            return $dbname;
        }

        return $this->dbName;
    }

    /**
     * Set the database name.
     *
     * @param string $dbName The new database name.
     * @return $this
     */
    public function setDbName($dbName) {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * Get the username used to connect to the test database.
     *
     * @return string Returns a username.
     */
    public function getDbUser() {
        return $_ENV['dbuser'];
    }

    /**
     * Get the password used to connect to the test database.
     *
     * @return string Returns a password.
     */
    public function getDbPassword() {
        return $_ENV['dbpass'];
    }

    /**
     * Create the database for installation.
     *
     * @param array $dbInfo The database connection information.
     */
    private function createDatabase($dbInfo) {
        unset($dbInfo['name']);
        $pdo = $this->createPDO($dbInfo);

        $dbname = $this->getDbName();
        $pdo->query("create database if not exists `$dbname`");
    }

    /**
     * Uninstall the application.
     */
    public function uninstall() {
        // Delete the database.
        $dbname = $this->getDbName();
        $dbInfo = $this->getDbInfo();
        unset($dbInfo['name']);
        $pdo = $this->createPDO($dbInfo);
        $pdo->query("drop database if exists `$dbname`");

        // Delete the config file.
        if (file_exists($this->config->defaultPath())) {
            unlink($this->config->defaultPath());
        }

        // Reset the config to defaults.
        $this->config->Data = [];
        $this->config->load(PATH_ROOT.'/conf/config-defaults.php');
    }
}

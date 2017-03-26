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
    private $baseUrl;

    private $dbName;

    public function __construct(\Gdn_Configuration $config, AddonModel $addonModel, ContainerInterface $container) {
        parent::__construct($config, $addonModel, $container);

        $this->setBaseUrl($_ENV['baseurl']);
    }

    public function install($data) {
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

        parent::install($data);
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
     * Set the dbName.
     *
     * @param mixed $dbName
     * @return $this
     */
    public function setDbName($dbName) {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * Get the baseUrl.
     *
     * @return mixed Returns the baseUrl.
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * Set the baseUrl.
     *
     * @param mixed $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->config->defaultPath($this->getConfigPath());
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
     * @return bool
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

        return true;
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

    private function getDbInfo() {
        return ['host' => 'localhost',
            'name' => $this->getDbName(),
            'user' => $this->getDbUser(),
            'password' => $this->getDbPassword()];
    }
}

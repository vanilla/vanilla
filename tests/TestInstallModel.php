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
    public function __construct(\Gdn_Configuration $config, AddonModel $addonModel, ContainerInterface $container, \Gdn_Request $request) {
        parent::__construct($config, $addonModel, $container);

        $this->config->Data = [];
        $this->config->load(PATH_ROOT.'/conf/config-defaults.php');
        $this->config->load($config->defaultPath(), 'Configuration', true);

        $this->setBaseUrl($request->url('/'));
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
//        $this->config->defaultPath($this->getConfigPath());
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
            'host' => 'localhost',
            'name' => $this->getDbName(),
            'user' => $this->getDbUser(),
            'password' => $this->getDbPassword()
        ];
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
        $pdo->query("use `$dbname`");
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

        // Clear all database related objects from the container.

    }
}

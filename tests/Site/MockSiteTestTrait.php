<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Site;

use Garden\Container\Container;
use Garden\Container\Tests\Fixtures\PdoDb;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Contracts\Site\Site;
use Vanilla\Http\InternalClient;
use Vanilla\Site\OwnSite;
use VanillaTests\TestInstallModel;

/**
 * @method Container container()
 * @method InternalClient api()
 */
trait MockSiteTestTrait {

    /** @var MockOwnSite */
    private $backupOwnSite;

    /** @var int */
    private $mockSiteCount = 0;

    /**
     * A mapping of tableAlias -> maxPrimaryKeyValue.
     * @var array
     */
    private $maxPrimaryKeys = [];

    /** @var string */
    private $previousDatabaseName;

    /**
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        parent::configureContainerBeforeStartup($container);
        self::configureMockSiteContainer($container);
    }

    /**
     * @param Container $container
     */
    protected static function configureMockSiteContainer(Container $container) {
        $container->rule(AbstractSiteProvider::class)
            ->setClass(MockSiteProvider::class)
            ->setShared(true)
            ->rule(OwnSite::class)
            ->setClass(MockOwnSite::class)
            ->setShared(true)
        ;
    }

    /**
     * Run various code with as if it's from a specific site.
     *
     * When the callback finishes running the following will happen:
     * - The previous site will be restored.
     * - All created content in the $cleanupTables will be deleted.
     *
     * @param array|Site $siteOrOverrides
     * @param callable $callable
     */
    protected function runWithMockedSite($siteOrOverrides, callable $callable) {
        $site = $this->mockCurrentSite($siteOrOverrides);

        $dbNameForSite = 'vanilla_test_node' . $site->getSiteID();

        $dbOffsetBase = max(1, $site->getSiteID());
        $dbOffset = $dbOffsetBase * 1000;
        $this->runWithDB($dbNameForSite, $dbOffset, $callable);
        $this->restoreOwnSite();
    }

    /**
     * Create a mock site.
     *
     * @param Site|array $siteOrOverrides Overrides to pass when constructuring the site. All values are defaulted.
     *
     * @return Site
     */
    protected function mockCurrentSite($siteOrOverrides): Site {
        $this->mockSiteCount++;
        $this->backupOwnSite();
        $ownSite = $this->getOwnSite();

        if ($siteOrOverrides instanceof Site) {
            $site = $siteOrOverrides;
        } else {
            $site = new Site(
                $siteOrOverrides['name'] ?? 'Mocked Site ' . $this->mockSiteCount,
                $siteOrOverrides['webUrl'] ?? 'http://vanilla.localhost/node' . $this->mockSiteCount,
                $siteOrOverrides['siteID'] ?? $this->mockSiteCount,
                $siteOrOverrides['accountID'] = $ownSite->getAccountID(),
                $siteOrOverrides['httpClient'] ?? $ownSite->getHttpClient()
            );
        }

        $ownSite->applyFrom($site);
        return $ownSite;
    }

    /**
     * Backup the current own site.
     */
    protected function backupOwnSite() {
        $this->backupOwnSite = clone $this->getOwnSite();
    }

    /**
     * @return Site
     */
    protected function restoreOwnSite(): Site {
        $backup = $this->backupOwnSite;
        $this->backupOwnSite = null;

        $this->getOwnSite()->applyFrom($backup);

        return $backup;
    }

    /**
     * @return MockOwnSite
     */
    protected function getOwnSite(): MockOwnSite {
        return $this->container()->get(OwnSite::class);
    }

    /**
     * Run a callable with a particular
     *
     * @param string $newDbName The name of the DB.
     * @param int $dbOffset A database offset to apply for auto-incrementing keys.
     * @param callable $callable The callable to exucute with the new DB.
     */
    private function runWithDB(string $newDbName, int $dbOffset, callable $callable) {
        $config = \Gdn::config();
        $previousDbName = $config->get('Database.Name');
        $previousUserID = \Gdn::session()->UserID;

        // Make the new database.
        $this->switchDatabases($newDbName, true);

        // Structure of the new database.

        // Make sure debug is on
        $previousDebug = debug();
        debug(true);
        $updateModel = new \UpdateModel();
        $updateModel->runStructure();
        debug($previousDebug);
        \PermissionModel::resetAllRoles();
        $this->api()->setUserID(1); // System.

        // Apply an offset to all tables to make their records stand out more.
        $this->applyAutoIncrementingOffset($dbOffset);

        // Run the callback.
        call_user_func($callable);

        // Restore the old DB.
        $this->switchDatabases($previousDbName);

        // Restore the old session.
        $this->api()->setUserID($previousUserID);
    }

    /**
     * Apply some offset to all tables in order to make their IDs more unique.
     *
     * @param int $offset The offest to use.
     */
    protected function applyAutoIncrementingOffset(int $offset) {
        $database = \Gdn::database();
        $tables = $database->query('show table status')->resultArray();
        $tableNames = array_column($tables, 'Name');

        // Maximum offset value.
        if ($offset > 1000000000) {
            $offset = min(1000000, $offset) + $offset % 10;
        }

        foreach ($tableNames as $tableName) {
            $database->query("ALTER TABLE $tableName AUTO_INCREMENT = $offset");
        }
    }

    /**
     * Switch the DB connection to a particular name.
     *
     * @param string $dbName
     * @param bool $makeFreshInstance Ensure the database is freshly created.
     */
    private function switchDatabases(string $dbName, bool $makeFreshInstance = false) {
        $db = \Gdn::database();
        $config = \Gdn::config();

        if ($makeFreshInstance) {
            // Nuke the existing database if it exists.
            $db->query("drop database if exists $dbName");

            // Create the new database.
            $db->query("create database $dbName");
        }

        $db->closeConnection();
        $config->saveToConfig('Database.Name', $dbName);
        $db->init();
        TestInstallModel::clearMemoryCaches();
    }
}

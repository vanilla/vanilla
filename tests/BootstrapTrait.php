<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;

trait BootstrapTrait {

    /** @var Bootstrap */
    private static $bootstrap;

    /** @var Container */
    private static $container;

    /**
     * Bootstrap the site.
     */
    public static function setUpBeforeClass(): void {
        self::setUpBeforeClassBootstrap();
    }

    /**
     * Set up everything we need to set up.
     */
    protected static function setUpBeforeClassBootstrap(): void {
        self::createContainer();
    }

    /**
     * @inheritDoc
     */
    public function setupBoostrapTrait() {
        $logger = $this->getTestLogger();
        $logger->clear();
    }

    /**
     * Create the container for the site.
     *
     * @return Container Returns a container.
     */
    protected static function createContainer() {
        $folder = static::getBootstrapFolderName();
        self::$bootstrap = new Bootstrap("http://vanilla.test/$folder");

        self::$container = new Container();
        self::$bootstrap->run(self::$container);

        return self::$container;
    }

    /**
     * Get the folder name to construct Bootstrap.
     *
     * @return string
     */
    protected static function getBootstrapFolderName() {
        return strtolower(EventManager::classBasename(get_called_class()));
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function tearDownAfterClass(): void {
        Bootstrap::cleanup(self::$container);
    }

    /**
     * Get the container for the site info.
     *
     * @return Container Returns a container with site dependencies.
     */
    protected static function container() {
        return self::$container;
    }

    /**
     * Get the Bootstrap.
     *
     * @return Bootstrap
     */
    protected static function bootstrap() {
        return self::$bootstrap;
    }

    /**
     * Assert that something was logged.
     *
     * @param array $filter The log filter.
     */
    public function assertLog($filter = []) {
        $logger = $this->getTestLogger();
        $item = $logger->search($filter);
        $this->assertNotNull($item, "Could not find expected log: ".json_encode($filter));
    }

    /**
     * Assert that the log has a message.
     *
     * @param string $message
     */
    public function assertLogMessage(string $message) {
        $logger = $this->getTestLogger();
        $this->assertTrue($logger->hasMessage($message), "The log doesn't have the message: ".$message);
    }

    /**
     * Run a callback with the following config and restore the config after.
     *
     * @param array $config The config to set.
     * @param callable $callback The code to run.
     * @return mixed Returns the result of the callback.
     */
    protected function runWithConfig(array $config, callable $callback) {
        /* @var \Gdn_Configuration $c */
        $c = $this->container()->get(\Gdn_Configuration::class);

        // Create a backup of the config.
        $bak = [];
        foreach ($config as $key => $value) {
            $bak[$key] = $c->get($key, null);
        }

        try {
            foreach ($config as $key => $value) {
                $c->set($key, $value, true, false);
            }

            $r = $callback();
            return $r;
        } finally {
            foreach ($bak as $key => $value) {
                $c->set($key, $value, true, false);
            }
        }
    }

    /**
     * @return TestLogger
     */
    protected static function getTestLogger(): TestLogger {
        /** @var TestLogger $logger */
        $logger = static::container()->get(TestLogger::class);
        return $logger;
    }

    /**
     * Configure the container for connecting to the database.
     */
    protected static function initializeDatabase() {
        /** @var TestInstallModel $model */
        $model = static::container()->get(TestInstallModel::class);

        /** @var \Gdn_Database $database */
        $database = static::container()->get(\Gdn_Database::class);

        $database->init([
            'Host' => $model->getDbHost(),
            'Dbname' => $model->getDbName(),
            'User' => $model->getDbUser(),
            'Password' => $model->getDbPassword(),
        ]);
    }
}

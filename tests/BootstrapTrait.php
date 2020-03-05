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
        self::createContainer();
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
}

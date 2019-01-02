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
    public static function setUpBeforeClass() {
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
    public static function tearDownAfterClass() {
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
}

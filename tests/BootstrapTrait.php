<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;

trait BootstrapTrait {
    /**
     * @var Container
     */
    protected static $container;

    /**
     * Bootstrap the site.
     */
    public static function setupBeforeClass() {
        self::$container = static::createContainer();
    }

    /**
     * Create the container for the site.
     *
     * @return Container Returns a container.
     */
    protected static function createContainer() {
        $folder = strtolower(EventManager::classBasename(get_called_class()));
        $bootstrap = new Bootstrap("http://vanilla.test/$folder");

        $container = new Container();
        $bootstrap->run($container);

        return $container;
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function teardownAfterClass() {
        Bootstrap::cleanup(self::container());
    }

    /**
     * Get the container for the site info.
     *
     * @return Container Returns a container with site dependencies.
     */
    protected static function container() {
        return self::$container;
    }
}

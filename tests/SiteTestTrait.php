<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;

/**
 * Allow a class to test against
 */
trait SiteTestTrait {
    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var array
     */
    protected static $siteInfo;

    /**
     * @var array The addons to install. Restored on teardownAfterClass();
     */
    protected static $addons = ['vanilla', 'conversations', 'stubcontent'];

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
     * Install the site.
     */
    public static function setUpBeforeClass() {
        $dic = self::$container = static::createContainer();

        /* @var TestInstallModel $installer */
        $installer = $dic->get(TestInstallModel::class);

        $installer->uninstall();
        $result = $installer->install([
            'site' => ['title' => EventManager::classBasename(get_called_class())],
            'addons' => static::$addons
        ]);

        // Start Authenticators
        $dic->get('Authenticator')->startAuthenticator();

        self::$siteInfo = $result;
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function teardownAfterClass() {
        self::$addons = ['vanilla', 'conversations', 'stubcontent'];
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

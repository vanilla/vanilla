<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;

/**
 * Allow a class to test against
 */
trait SiteTestTrait {
    use BootstrapTrait {
        setupBeforeClass as private bootstrapBeforeClass;
        teardownAfterClass as private bootstrapAfterClass;
    }

    /**
     * @var array
     */
    protected static $siteInfo;

    /**
     * @var array The addons to install. Restored on teardownAfterClass();
     */
    protected static $addons = ['vanilla', 'conversations', 'stubcontent'];

    /**
     * Install the site.
     */
    public static function setupBeforeClass() {
        static::bootstrapBeforeClass();

        $dic = self::$container;

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
        static::bootstrapAfterClass();
    }
}

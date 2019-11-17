<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

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
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return static::$addons;
    }

    /**
     * Install the site.
     */
    public static function setupBeforeClass() {
        self::symlinkAddonFixtures();
        static::bootstrapBeforeClass();

        $dic = self::$container;

        /* @var TestInstallModel $installer */
        $installer = $dic->get(TestInstallModel::class);

        $installer->uninstall();
        $result = $installer->install([
            'site' => ['title' => EventManager::classBasename(get_called_class())],
            'addons' => static::getAddons(),
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
        self::unSymlinkAddonFixtures();
    }

    private static function symlinkAddonFixtures(): void {
        self::mapAddonFixtures(function (string $path, string $dest): void {
            if (file_exists($dest)) {
                if (realpath($dest) !== realpath($path)) {
                    throw new AssertionFailedError("Cannot symlink addon fixture: $path");
                }
            } else {
                symlink($path, $dest);
            }
        });
    }

    private static function unSymlinkAddonFixtures(): void {
        self::mapAddonFixtures(function (string $path, string $dest): void {
            if (file_exists($dest) && realpath($dest) === realpath($path)) {
                unlink($dest);
            }
        });
    }

    private static function mapAddonFixtures(callable $callback): void {
        $testAddonPaths = array_merge(
            glob(PATH_ROOT . '/tests/addons/*', GLOB_ONLYDIR),
            glob(PATH_ROOT . '/plugins/*/tests/plugins/*', GLOB_ONLYDIR),
        );
        foreach ($testAddonPaths as $path) {
            $dirname = basename($path);

            $dest = PATH_ROOT . "/plugins/$dirname";

            $callback($path, $dest);
        }
    }
}

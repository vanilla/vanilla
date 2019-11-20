<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\EventManager;
use PHPUnit\Framework\AssertionFailedError;
use Vanilla\Contracts\ConfigurationInterface;

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
     * @var array
     */
    private static $symLinkedAddons;

    /**
     * @var array The addons to install. Restored on teardownAfterClass();
     */
    protected static $addons = ['vanilla', 'conversations', 'stubcontent'];

    /** @var array $enabledLocales */
    protected static $enabledLocales = [];

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

        self::preparelocales();

        // Start Authenticators
        $dic->get('Authenticator')->startAuthenticator();

        self::$siteInfo = $result;
    }

    /**
     * Create locale directory and locale definitions.php
     */
    public static function preparelocales() {
        $enabledLocales =[];
        foreach (static::$enabledLocales as $localeKey => $locale) {
            $enabledLocales["test_$localeKey"] = $locale;
            $localeDir = PATH_ROOT."/locales/test_$localeKey";
            if (!(file_exists($localeDir) && is_dir($localeDir))) {
                mkdir($localeDir);
            }
            $localeFile = $localeDir.'/definitions.php';
            if (!file_exists($localeFile)) {
                $handle = fopen($localeFile, "w");
                $localeDefinitions = <<<TEMPLATE
<?php

 \$LocaleInfo['$localeKey'] = array (
  'Locale' => '$locale',
  'Name' => '$locale / locale',
  'EnName' => '$locale Name',
  'Description' => 'Official $locale description',
  'Version' => '000',
  'Author' => 'Vanilla Community',
  'AuthorUrl' => 'https://www.transifex.com/projects/p/vanilla/language/$locale/',
  'License' => 'none',
  'PercentComplete' => 100,
  'NumComplete' => 0,
  'DenComplete' => 0,
  'Icon' => '$locale.svg',
);

TEMPLATE;
                fwrite($handle, $localeDefinitions);
                fclose($handle);
            }
        }
        if (!empty($enabledLocales)) {
            /** @var ConfigurationInterface $config */
            $config = self::container()->get(ConfigurationInterface::class);
            $config->set('EnabledLocales', $enabledLocales, true);
        }
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function teardownAfterClass() {
        self::$addons = ['vanilla', 'conversations', 'stubcontent'];
        static::bootstrapAfterClass();
        self::unSymlinkAddonFixtures();
    }

    /**
     * Symlink all addon fixtures.
     */
    private static function symlinkAddonFixtures(): void {
        self::mapAddonFixtures(function (string $path, string $dest): void {
            if (file_exists($dest)) {
                if (realpath($dest) !== realpath($path)) {
                    throw new AssertionFailedError("Cannot symlink addon fixture: $path");
                }
            } else {
                self::$symLinkedAddons[$path] = $dest;

                symlink($path, $dest);
            }
        });
    }

    /**
     * Remove symlinks to all addon fixtures.
     */
    private static function unSymlinkAddonFixtures(): void {
        self::mapAddonFixtures(function (string $path, string $dest): void {
            if (isset(self::$symLinkedAddons[$path]) && file_exists($dest) && realpath($dest) === realpath($path)) {
                unlink($dest);
            }
        });
        self::$symLinkedAddons = [];
    }

    /**
     * Run a callback on all test addon fixtures.
     *
     * @param callable $callback
     */
    private static function mapAddonFixtures(callable $callback): void {
        $testAddonPaths = array_merge(
            glob(PATH_ROOT . '/tests/addons/*', GLOB_ONLYDIR),
            glob(PATH_ROOT . '/plugins/*/tests/plugins/*', GLOB_ONLYDIR)
        );
        foreach ($testAddonPaths as $path) {
            $dirname = basename($path);

            $dest = PATH_ROOT . "/plugins/$dirname";

            $callback($path, $dest);
        }
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;
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
     * @var array The addons to install. Restored on teardownAfterClass();
     */
    protected static $addons = ['vanilla', 'conversations', 'stubcontent'];

    /** @var array $enabledLocales */
    protected static $enabledLocales = [];

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

        self::preparelocales();

        // Start Authenticators
        $dic->get('Authenticator')->startAuthenticator();

        self::$siteInfo = $result;
    }

    /**
     * Create locale directory and locale definitions.php
     */
    public static function preparelocales() {
        foreach(static::$enabledLocales as $localeKey => $locale) {
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
            /** @var ConfigurationInterface $config */
            $config = self::container()->get(ConfigurationInterface::class);
            $config->set('EnabledLocales', static::$enabledLocales, true);
        }
    }

    /**
     * Cleanup the container after testing is done.
     */
    public static function teardownAfterClass() {
        self::$addons = ['vanilla', 'conversations', 'stubcontent'];
        static::bootstrapAfterClass();
    }
}

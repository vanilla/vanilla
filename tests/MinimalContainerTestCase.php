<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Http\HttpClient;
use Garden\Web\RequestInterface;
use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\AddonManager;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Formatting\FormatService;
use Vanilla\InjectableInterface;
use VanillaTests\Fixtures\MockAddonProvider;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\Fixtures\MockLocale;
use VanillaTests\Fixtures\NullCache;

/**
 * A very minimal PHPUnit test case using Garden\Container.
 *
 * Provides minimal default container configuration.
 * Please try to avoid bloating this the size of BootstrapTrait.
 *
 * Highlights:
 * - Little/No IO.
 * - No DB.
 * - No Config.
 * - No localization.
 * - No addon manager.
 * - No cache.
 */
class MinimalContainerTestCase extends TestCase {

    protected $baseUrl = 'http://vanilla.test/minimal-container-test/';

    /**
     * Setup the container.
     */
    private function configureContainer() {
        \Gdn::setContainer(new Container());
        self::container()
            ->rule(FormatService::class)
            ->addCall('registerBuiltInFormats', [self::container()])

            // Mocks of interfaces.
            // Addons
            ->rule(AddonProviderInterface::class)
            ->setClass(MockAddonProvider::class)
            ->addAlias(AddonManager::class)
            ->addAlias(\Gdn::AliasAddonManager)
            ->setShared(true)

            // Config
            ->rule(ConfigurationInterface::class)
            ->setClass(MockConfig::class)
            ->addAlias(\Gdn_Configuration::class)
            ->addAlias(\Gdn::AliasConfig)
            ->setShared(true)
            ->rule(MockConfig::class)
            ->setAliasOf(ConfigurationInterface::class)
            ->setShared(true)

            // Locale
            ->rule(LocaleInterface::class)
            ->setClass(MockLocale::class)
            ->addAlias(\Gdn_Locale::class)
            ->addAlias(\Gdn::AliasLocale)
            ->setShared(true)
            ->rule(MockLocale::class)
            ->setAliasOf(LocaleInterface::class)
            ->setShared(true)

            // Prevent real HTTP requests.
            ->rule(HttpClient::class)
            ->setClass(MockHttpClient::class)

            // Dates
            ->rule(\DateTimeInterface::class)
            ->setAliasOf(\DateTimeImmutable::class)
            ->setConstructorArgs([null, null])

            ->rule(\Gdn_Cache::class)
            ->setAliasOf(NullCache::class)
            ->addAlias(\Gdn::AliasCache)
            ->setInstance(NullCache::class, new NullCache())

            ->rule(InjectableInterface::class)
            ->addCall('setDependencies')

            ->rule(\Gdn_Request::class)
            ->setShared(true)
            ->addAlias(Gdn::AliasRequest)
            ->addAlias(RequestInterface::class)
        ;
    }

    /**
     * Set some configuration key for the tests.
     *
     * @param string $key The config key.
     * @param mixed $value The value to set.
     */
    public static function setConfig(string $key, $value) {
        /** @var MockConfig $config */
        $config = self::container()->get(ConfigurationInterface::class);
        $config->set($key, $value);
    }

    /**
     * Set multiple configuration keys for the tests.
     *
     * @param array $configs An array of $configKey => $value
     */
    public static function setConfigs(array $configs) {
        /** @var MockConfig $config */
        $config = self::container()->get(MockConfig::class);
        $config->loadData($configs);
    }

    /**
     * Set some translation key for the tests.
     *
     * @param string $key The translation key.
     * @param mixed $value The value to set.
     */
    public static function setTranslation(string $key, $value) {
        /** @var MockConfig $config */
        $config = self::container()->get(MockLocale::class);
        $config->set($key, $value);
    }

    /**
     * Set multiple translation keys for the tests.
     *
     * @param array $configs An array of $translationKey => $value
     */
    public static function setTranslations(array $configs) {
        /** @var MockConfig $config */
        $config = self::container()->get(MockLocale::class);
        $config->loadData($configs);
    }

    /**
     * Do some pre-test setup.
     */
    public function setUp() {
        parent::setUp();
        $this->setGlobals();
        $this->configureContainer();
    }

    /**
     * Set the global variables that have dependencies.
     */
    private function setGlobals() {
        // Set some server globals.
        $baseUrl = $this->baseUrl;
        $_SERVER['REMOTE_ADDR'] = '::1'; // Simulate requests from local IPv6 address.
        $_SERVER['HTTP_HOST'] = parse_url($baseUrl, PHP_URL_HOST);
        $_SERVER['SERVER_PORT'] = parse_url($baseUrl, PHP_URL_PORT) ?: null;
        $_SERVER['SCRIPT_NAME'] = parse_url($baseUrl, PHP_URL_PATH);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['HTTPS'] = parse_url($baseUrl, PHP_URL_SCHEME) === 'https';
    }


    /**
     * Reset the container.
     */
    public static function tearDownAfterClass() {
        \Gdn::setContainer(new NullContainer());
    }

    /**
     * @return Container
     */
    protected static function container(): Container {
        return \Gdn::getContainer();
    }
}

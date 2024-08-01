<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\EventManager;
use Garden\Http\HttpClient;
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Web\RequestInterface;
use Gdn;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Contracts\Models\UserProviderInterface;
use Vanilla\Contracts\Web\UASnifferInterface;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Web\TwigEnhancer;
use VanillaTests\Fixtures\MockUASniffer;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\InjectableInterface;
use Vanilla\Site\SingleSiteSectionProvider;
use Vanilla\Utility\ContainerUtils;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockLocale;
use VanillaTests\Fixtures\Models\MockUserProvider;
use VanillaTests\Fixtures\NullCache;
use VanillaTests\Fixtures\SpyingEventManager;

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
class MinimalContainerTestCase extends TestCase
{
    protected $baseUrl = "https://vanilla.test/minimal-container-test/";

    /**
     * @return string
     */
    public static function getBaseUrl()
    {
        return "https://vanilla.test/minimal-container-test";
    }

    /**
     * Whether or not we should apply the common bootstrap.
     *
     * @return bool
     */
    protected static function useCommonBootstrap(): bool
    {
        return true;
    }

    /**
     * Setup the container.
     */
    protected function configureContainer()
    {
        $container = new Container();

        if (static::useCommonBootstrap()) {
            \Vanilla\Bootstrap::configureContainer($container);
        }

        $container
            ->rule(\Gdn_Session::class)
            ->setShared(true)
            ->rule(Container::class)
            ->addAlias(ContainerInterface::class)
            ->setInstance(Container::class, $container)
            ->rule(FormatService::class)
            ->setShared(true)
            ->addCall("registerBuiltInFormats")

            ->rule(Parser::class)
            ->addCall("addCoreBlotsAndFormats")

            // Site sections
            ->rule(\Vanilla\Contracts\Site\SiteSectionProviderInterface::class)
            ->setClass(SingleSiteSectionProvider::class)
            ->setShared(true)

            ->rule(TwigEnhancer::class)
            ->setConstructorArgs(["bannerImageModel" => null])

            // Mocks of interfaces.
            // Addons
            ->rule(AddonManager::class)
            ->setClass(MockAddonManager::class)
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

            ->setInstance("@baseUrl", $this->baseUrl)

            ->rule(\Vanilla\Web\Asset\DeploymentCacheBuster::class)
            ->setShared(true)
            ->setConstructorArgs([
                "deploymentTime" => null,
            ])

            // Prevent real HTTP requests.
            ->rule(HttpClient::class)
            ->addCall("setHandler", [new Reference(MockHttpHandler::class)])

            // Prevent real HTTP requests.
            ->rule(EventManager::class)
            ->setClass(SpyingEventManager::class)
            ->setShared(true)

            ->rule(UASnifferInterface::class)
            ->setClass(MockUASniffer::class)

            // Dates
            ->rule(\DateTimeInterface::class)
            ->setAliasOf(\DateTimeImmutable::class)
            ->setConstructorArgs([null, null])

            // Logger
            ->rule(\Vanilla\Logger::class)
            ->setShared(true)
            ->addAlias(LoggerInterface::class)

            ->rule(LoggerAwareInterface::class)
            ->addCall("setLogger")

            ->rule(\Gdn_Cache::class)
            ->setAliasOf(NullCache::class)
            ->addAlias(\Gdn::AliasCache)
            ->setInstance(NullCache::class, new NullCache())

            ->rule(InjectableInterface::class)
            ->addCall("setDependencies")

            ->rule(\Gdn_Request::class)
            ->setShared(true)
            ->addAlias(Gdn::AliasRequest)
            ->addAlias(RequestInterface::class)

            ->rule(UserProviderInterface::class)
            ->setClass(MockUserProvider::class)
            ->setShared(true)

            ->rule(ThemeFeatures::class)
            ->setConstructorArgs(["theme" => ContainerUtils::currentTheme()])

            ->rule(\Gdn_PluginManager::class)
            ->addAlias(\Gdn::AliasPluginManager)

            ->setInstance(\Gdn_PluginManager::class, $this->createMock(\Gdn_PluginManager::class));

        \Gdn::setContainer($container);
    }

    /**
     * Set information about the current user in the session.
     * @param array $info The user information to set.
     */
    public function setUserInfo(array $info)
    {
        $session = $this->container()->get(\Gdn_Session::class);

        foreach ($info as $key => $value) {
            if ($key === "UserID") {
                $session->UserID = $value;
            }

            if ($key === "Admin" && $value > 0) {
                $session->getPermissions()->setAdmin(true);
            }

            $session->User = new \stdClass();
            $session->User->{$key} = $value;
        }
    }

    /**
     * Set some configuration key for the tests.
     *
     * @param string $key The config key.
     * @param mixed $value The value to set.
     */
    public static function setConfig(string $key, $value)
    {
        self::getConfig()->set($key, $value);
    }

    /**
     * Set multiple configuration keys for the tests.
     *
     * @param array $configs An array of $configKey => $value
     */
    public static function setConfigs(array $configs)
    {
        self::getConfig()->loadData($configs);
    }

    /**
     * Get the config object.
     */
    public static function getConfig(): ConfigurationInterface
    {
        return self::container()->get(ConfigurationInterface::class);
    }

    /**
     * Set some translation key for the tests.
     *
     * @param string $key The translation key.
     * @param mixed $value The value to set.
     */
    public static function setTranslation(string $key, $value)
    {
        /** @var MockConfig $config */
        $config = self::container()->get(MockLocale::class);
        $config->set($key, $value);
    }

    /**
     * Set multiple translation keys for the tests.
     *
     * @param array $configs An array of $translationKey => $value
     */
    public static function setTranslations(array $configs)
    {
        /** @var MockConfig $config */
        $config = self::container()->get(MockLocale::class);
        $config->loadData($configs);
    }

    /**
     * Do some pre-test setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setGlobals();
        $this->configureContainer();
        $this->setConfigs([
            "Garden.Html.AllowedUrlSchemes" => ["http", "https"],
        ]);
    }

    /**
     * Set the global variables that have dependencies.
     */
    private function setGlobals()
    {
        // Set some server globals.
        $baseUrl = $this->baseUrl;
        $_SERVER["REMOTE_ADDR"] = "::1"; // Simulate requests from local IPv6 address.
        $_SERVER["HTTP_HOST"] = parse_url($baseUrl, PHP_URL_HOST);
        $_SERVER["SERVER_PORT"] = parse_url($baseUrl, PHP_URL_PORT) ?: null;
        $_SERVER["SCRIPT_NAME"] = parse_url($baseUrl, PHP_URL_PATH);
        $_SERVER["PATH_INFO"] = "";
        $_SERVER["HTTPS"] = parse_url($baseUrl, PHP_URL_SCHEME) === "https";
    }

    /**
     * @return MockUserProvider
     */
    protected function getMockUserProvider(): MockUserProvider
    {
        return self::container()->get(UserProviderInterface::class);
    }

    /**
     * Reset the container.
     */
    public static function tearDownAfterClass(): void
    {
        \Gdn::setContainer(new NullContainer());
    }

    /**
     * @return Container
     */
    protected static function container(): Container
    {
        return \Gdn::getContainer();
    }
}

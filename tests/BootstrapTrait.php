<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\Cache\StaticCache;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\FeatureFlagHelper;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Fixtures\TestCache;
use Webmozart\Assert\Assert;

trait BootstrapTrait {
    use PrivateAccessTrait;

    /** @var Bootstrap */
    private static $bootstrap;

    /** @var Container */
    private static $container;

    /** @var TestDispatcher */
    private static $bessy;

    /**
     * @var CapturedEmail[]
     */
    protected static $emails;

    /** @var string */
    protected $controllerRawOutput;

    /**
     * Bootstrap the site.
     */
    public static function setUpBeforeClass(): void {
        self::setUpBeforeClassBootstrap();
    }

    /**
     * Set up everything we need to set up.
     */
    protected static function setUpBeforeClassBootstrap(): void {
        self::createContainer();

        /** @var EventManager $events */
        $events = \Gdn::getContainer()->get(EventManager::class);
        $events->bind('gdn_email_beforeSendMail', function (\Gdn_Email $email) {
            $captured = CapturedEmail::fromEmail($email);

            // Put most recent first.
            array_unshift(self::$emails, $captured);
        });
    }

    /**
     * @inheritDoc
     */
    public function setUpBootstrap() {
        \Gdn::setController(null);
        StaticCache::clear();

        $logger = $this->getTestLogger();
        $logger->clear();

        self::$emails = [];
        \Gdn_Form::resetIDs();
    }

    /**
     * Create the container for the site.
     *
     * @return Container Returns a container.
     */
    protected static function createContainer() {
        $folder = static::getBootstrapFolderName();
        if ($folder !== "") {
            $folder = "/" . $folder;
        }
        self::$bootstrap = new Bootstrap("http://vanilla.test{$folder}");

        self::$container = new Container();
        self::$bootstrap->run(self::$container);
        self::$bessy = self::$container->get(TestDispatcher::class);
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
     * Get the current session class.
     *
     * @return \Gdn_Session
     */
    protected function getSession(): \Gdn_Session {
        return $this->container()->get(\Gdn_Session::class);
    }

    /**
     * Assert that an email was sent to a recipient and return it.
     *
     * @param string|null $email The email address to search.
     * @param string|null $name The name to search.
     * @param string $message An error message if the test fails.
     * @return CapturedEmail
     */
    public static function assertEmailSentTo(?string $email, ?string $name = null, string $message = ''): CapturedEmail {
        foreach (self::$emails as $row) {
            $found = $row->findRecipient($email, $name);
            if ($found !== null) {
                return $row;
            }
        }
        self::fail($message ?: "Email not found: $email $name");
    }

    /**
     * Get the container for the site info.
     *
     * @return Container Returns a container with site dependencies.
     */
    protected static function container(): Container {
        return self::$container;
    }

    /**
     * Get the dispatcher for old controllers.
     *
     *          __n__n__
     *   .------`-\00/-'
     *  /  ##  ## (oo)
     * / \## __   ./
     *    |//YY \|/
     *    |||   |||
     *
     * @return TestDispatcher Returns a dispatcher.
     */
    protected static function bessy(): TestDispatcher {
        return self::$bessy;
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
     * Assert that something was logged.
     *
     * @param array $filter The log filter.
     */
    public function assertLog($filter = []) {
        $logger = $this->getTestLogger();
        $item = $logger->search($filter);
        $this->assertNotNull($item, "Could not find expected log: ".json_encode($filter));
    }

    /**
     * Assert that something was NOT logged.
     *
     * @param array $filter The log filter.
     */
    public function assertNoLog($filter = []) {
        $logger = $this->getTestLogger();
        $item = $logger->search($filter);
        $this->assertNull($item, "Unexpected log found: ".json_encode($filter));
    }

    /**
     * Assert that the log has a message.
     *
     * @param string $message
     */
    public function assertLogMessage(string $message) {
        $logger = $this->getTestLogger();
        $this->assertTrue($logger->hasMessage($message), "The log doesn't have the message: ".$message);
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

    /**
     * @return TestLogger
     */
    protected static function getTestLogger(): TestLogger {
        /** @var TestLogger $logger */
        $logger = static::container()->get(TestLogger::class);
        return $logger;
    }

    /**
     * Configure the container for connecting to the database.
     *
     * @deprecated This logic has been moved into the container config.
     */
    protected static function initializeDatabase() {
    }

    /**
     * Reset necessary tables.
     *
     * @param string $name
     */
    protected static function resetTable(string $name): void {
        \Gdn::database()->sql()->truncate($name);
    }

    /**
     * Enable caching for tests.
     *
     * Call this in your `setUp()` method to install caching for your tests.
     *
     * @return TestCache
     */
    protected static function enableCaching(): TestCache {
        $cache = new TestCache();
        static::container()->setInstance(\Gdn_Cache::class, $cache);
        static::container()->setInstance(CacheInterface::class, new CacheCacheAdapter($cache));
        static::container()->get(ConfigurationInterface::class)->set('Cache.Enabled', true);
        return $cache;
    }

    /**
     * Enable a feature flag.
     *
     * @param string $feature The config-friendly name of the feature.
     */
    public static function enableFeature(string $feature) {
        static::container()->get(ConfigurationInterface::class)->set("Feature.{$feature}.Enabled", true);
        FeatureFlagHelper::clearCache();
    }

    /**
     * Dispatch a legacy controller endpoint and return the controller instead of rendering.
     *
     * @param string|\Gdn_Request|array|null $request The request to dispatch. This can be a string URL or a Gdn_Request object.
     * @param bool $permanent Whether or not to set {@link Gdn::request()} with the dispatched request. $request
     * @param string $deliveryType The delivery type to render with.
     *
     * @return \Gdn_Controller Returns the dispatched controller.
     * @deprecated Use `$this->bessy()->get()`.
     */
    protected function dispatchController($request = null, $permanent = true, string $deliveryType = DELIVERY_TYPE_VIEW): \Gdn_Controller {
        $dispatcher = \Gdn::dispatcher();
        $fn = function () use ($deliveryType) {
            $this->deliveryType = $deliveryType;
        };
        $fn = $fn->bindTo($dispatcher, $dispatcher);
        $fn();

        /** @var EventManager $events */
        $events = self::$container->get(EventManager::class);

        $controller = null;
        $fn = function (\Gdn_Controller $sender) use (&$controller, $deliveryType) {
            $controller = $sender;
            $controller->deliveryType($deliveryType);
        };
        $events->bind('base_render_before', $fn);

        if (is_string($request)) {
            $request = ['GET', $request, []];
        }

        if (is_array($request)) {
            [$method, $path, $post] = $request + ['', '/', []];
            $request = \Gdn_Request::create()->fromEnvironment()->setMethod($method)->setUrl($path);
            if ($method === 'POST') {
                \Gdn::session()->validateTransientKey(true);
                $request->setRequestArguments(\Gdn_Request::INPUT_POST, $post);
            }
        }

        try {
            // Capture output.
            ob_start();
            $dispatcher->dispatch($request, $permanent);
            $output = ob_get_contents();
            $this->controllerRawOutput = $output;
            ob_end_clean();
        } finally {
            $events->unbind('base_render_before', $fn);
        }

        if ($controller === null) {
            throw new \Exception("The controller was not properly rendered.");
        }

        return $controller;
    }

    /**
     * Dispatch a legacy controller endpoint and return its data instead of rendering.
     *
     * @param string|\Gdn_Request|null $request The request to dispatch. This can be a string URL or a Gdn_Request object.
     * @param bool $permanent Whether or not to set {@link Gdn::request()} with the dispatched request. $request
     * @return mixed Returns the dispatched controller's data property.
     * @deprecated Use `$this->bessy()->get()->Data`.
     */
    public function dispatchData($request = null, $permanent = true) {
        $controller = $this->dispatchController($request, $permanent, DELIVERY_TYPE_VIEW);
        return $controller->Data;
    }

    /**
     * Dispatch a legacy controller endpoint and return its data instead of rendering.
     *
     * @param string|\Gdn_Request|null $request The request to dispatch. This can be a string URL or a Gdn_Request object.
     * @param bool $permanent Whether or not to set {@link Gdn::request()} with the dispatched request. $request
     * @param string $deliveryType The delivery type to render with.
     * @return TestHtmlDocument Returns the dispatched controller's data property.
     * @deprecated Use `$this->bessy()->getHtml()`.
     */
    public function dispatchControllerHtml($request = null, $permanent = true, string $deliveryType = DELIVERY_TYPE_VIEW): TestHtmlDocument {
        $allowedDeliveryTypes = [
            DELIVERY_TYPE_VIEW,
            DELIVERY_TYPE_ALL,
            DELIVERY_METHOD_XHTML,
        ];
        Assert::inArray($deliveryType, $allowedDeliveryTypes);
        $this->dispatchController($request, $permanent, $deliveryType);
        Assert::string($this->controllerRawOutput, 'Control must output HTML');
        $document = new TestHtmlDocument($this->controllerRawOutput);
        return $document;
    }

    /**
     * Ensure a dataset has a row matching a filter.
     *
     * @param array $rows The array to search.
     * @param array $filter The filter to pass to it.
     * @param string $message The error message.
     * @return mixed Returns the matching row.
     * @deprecated Use `VanillaTestCase::assertDatasetHasRow()`.
     */
    public static function assertArrayHasRow(array $rows, array $filter, string $message = '') {
        VanillaTestCase::assertDatasetHasRow($rows, $filter, $message);
    }

    /**
     * Ensure a dataset has a row matching a filter.
     *
     * @param array $rows The array to search.
     * @param array $filter The filter to pass to it.
     * @param string $message The error message.
     * @return mixed Returns the matching row.
     * @deprecated Use `VanillaTestCase::assertDatasetMatchesFilter()`.
     */
    public static function assertArrayMatchesFilter(array $rows, array $filter, string $message = '') {
        VanillaTestCase::assertDatasetMatchesFilter($rows, $filter, $message);
    }

    /**
     * Create a dummy user for testing.
     *
     * Note that this does NOT insert the user into the database. For that see `SiteTestTrait::insertDummyUser()`.
     *
     * You can put `'%s'` in value strings to be replaced by the internal user counter. This helps keep things unique
     * for repeated tests.
     *
     * @param array $overrides Override or add user fields.
     * @return array Returns a user array.
     */
    protected function dummyUser(array $overrides = []): array {
        static $i = 1;

        foreach ($overrides as &$value) {
            if (is_string($value)) {
                $value = sprintf($value, $i);
            }
        }

        $user = array_replace(
            ['Name' => 'user'.$i, 'Email' => "user$i@example.com"],
            $overrides
        );
        $i++;
        return $user;
    }

    /**
     * Assert
     *
     * @param \Gdn_Model $model
     */
    protected function assertModelVal(\Gdn_Model $model): void {
        if (!empty($model->Validation->results())) {
            TestCase::fail($model->Validation->resultsText());
        }
    }
}

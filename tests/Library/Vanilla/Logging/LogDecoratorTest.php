<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use Garden\Http\HttpClient;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\Site\Site;
use Vanilla\Logger;
use Vanilla\Logging\LogDecorator;
use Vanilla\Site\OwnSite;
use VanillaTests\Site\MockOwnSite;
use VanillaTests\SiteTestCase;
use VanillaTests\TestLogger;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the `LogDecorator` class.
 */
class LogDecoratorTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * @var LogDecorator
     */
    private $log;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->reset();
    }

    /**
     * Reset the logger instance.
     */
    private function reset()
    {
        $this->container()->setInstance(LogDecorator::class, null);
        $this->container()->setInstance(LoggerInterface::class, null);
        $this->container()->setInstance(TestLogger::class, null);
        $logger = $this->container()->get(TestLogger::class);
        $this->log = $this->container()->getArgs(LogDecorator::class, ["logger" => $logger]);
    }

    /**
     * Test the basic decoration workflow.
     */
    public function testBasicDecoration()
    {
        $this->log->info("foo");
        $this->assertLog(["userID" => \Gdn::session()->UserID]);
    }

    /**
     * Test the decorator's getter/setters.
     */
    public function testGetterSetter()
    {
        $this->log->setContextOverrides(["foo" => "bar"]);
        $this->assertSame(["foo" => "bar"], $this->log->getContextOverrides());
        $this->log->addStaticContextDefaults(["baz" => "fra"]);
        $this->assertSame(["foo" => "bar", "baz" => "fra"], $this->log->getContextOverrides());

        $this->log->info("foo");
        $this->assertLog(["foo" => "bar", "baz" => "fra"]);
    }

    /**
     * A passed context should override the default context.
     */
    public function testOverrideUserID()
    {
        $this->log->info("foo", [Logger::FIELD_USERID => 123]);
        $this->assertLog([
            "message" => "foo",
            "userID" => 123,
        ]);
    }

    /**
     * Test basic context cleaning.
     */
    public function testObscureContext(): void
    {
        $this->log->info("foo", ["a" => ["ClientSecret" => "a", "Password" => "b"]]);
        $this->assertLog([
            "data.a.ClientSecret" => "***",
            "data.a.Password" => "***",
        ]);
    }

    /**
     * Test that we include the request in the context.
     */
    public function testIncludesRequestContext()
    {
        \Gdn::request()->fromImport(
            $this->bessy()
                ->createRequest("POST", "/my-path/nested")
                ->setIP("0.0.0.5")
        );

        $this->log->info("foo");
        $this->assertLog([
            "message" => "foo",
            "request.method" => "POST",
            "request.protocol" => "https",
            "request.hostname" => "vanilla.test",
            "request.path" => "/my-path/nested",
            "request.clientIP" => "0.0.0.5",
        ]);
    }

    /**
     * Test that decoration includes site context.
     */
    public function testSiteContext()
    {
        $mockOwnSite = self::container()->get(MockOwnSite::class);
        $mockOwnSite->applyFrom(new Site("site", "https://test.com", "100", "500", new HttpClient()));
        self::container()->setInstance(OwnSite::class, $mockOwnSite);
        $this->reset();
        $this->log->info("foo");
        $this->assertLog([
            "site" => [
                "version" => APPLICATION_VERSION,
                "siteID" => "100",
                "accountID" => "500",
            ],
            "message" => "foo",
        ]);
    }

    /**
     * Test that certain fields are managed properly.
     */
    public function testHoistedFields()
    {
        $data = [
            Logger::FIELD_USERID => 100,
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
            Logger::FIELD_EVENT => "hello_event",
            Logger::FIELD_TAGS => ["tag1", "tag2"],
        ];
        $expectedData = array_merge_recursive([Logger::FIELD_TAGS => ["hello", "event"]], $data);
        $this->log->info("foo", $data);
        $this->assertLog($expectedData);
        $this->assertNoLog([
            "data" => $expectedData,
        ]);
    }

    /**
     * Test that unknown fields are nested into data.
     */
    public function testNestedFields()
    {
        $data = [
            "item1" => true,
            "item2" => false,
            "nestedFurther" => [
                "foo" => "bar",
            ],
        ];
        $this->log->info("foo", $data);
        $this->assertLog(["data" => $data]);
        $this->assertNoLog($data);
    }

    /**
     * Test user lookups for logs.
     */
    public function testUserIDLookup()
    {
        $user = $this->createUser(["name" => "myuser"]);
        $data = [
            Logger::FIELD_USERID => $user["userID"],
            Logger::FIELD_TARGET_USERID => 99999999,
        ];
        $this->log->info("foo", $data);
        $this->assertLog([
            Logger::FIELD_USERID => $user["userID"],
            Logger::FIELD_USERNAME => "myuser",
            Logger::FIELD_TARGET_USERID => 99999999,
            Logger::FIELD_TARGET_USERNAME => "unknown",
        ]);
    }
}

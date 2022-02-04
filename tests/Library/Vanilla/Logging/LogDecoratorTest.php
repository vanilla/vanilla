<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use PHPUnit\Framework\TestCase;
use Vanilla\Logging\LogDecorator;
use VanillaTests\TestLogger;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `LogDecorator` class.
 */
class LogDecoratorTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var LogDecorator
     */
    private $log;

    /**
     * @var TestLogger
     */
    private $testLogger;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupSiteTestTrait();

        $logger = $this->container()->get(TestLogger::class);
        $this->log = $this->container()->getArgs(LogDecorator::class, ['logger' => $logger]);
    }

    /**
     * Test the basic decoration workflow.
     */
    public function testBasicDecoration() {
        $this->log->info('foo');
        $this->assertLog(['userid' => \Gdn::session()->UserID]);
    }

    /**
     * Test the decorator's getter/setters.
     */
    public function testGetterSetter() {
        $this->log->setContextOverrides(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $this->log->getContextOverrides());
        $this->log->addStaticContextDefaults(['baz' => 'fra']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'fra'], $this->log->getContextOverrides());

        $this->log->info('foo');
        $this->assertLog(['foo' => 'bar', 'baz' => 'fra']);
    }

    /**
     * A passed context should override the default context.
     */
    public function testOverride() {
        $this->log->info('foo', ['userid' => 123]);
        $this->assertLog([
            'message' => 'foo',
            'userid' => 123,
        ]);
    }

    /**
     * Test basic context cleaning.
     */
    public function testObscureContext(): void {
        $this->log->info('foo', ['a' => ['ClientSecret' => 'a', 'Password' => 'b']]);
        $this->assertLog(['a' => ['ClientSecret' => '***', 'Password' => '***']]);
    }
}

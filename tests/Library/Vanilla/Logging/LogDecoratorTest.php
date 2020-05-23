<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use PHPUnit\Framework\TestCase;
use Vanilla\Logging\LogDecorator;
use VanillaTests\BootstrapTrait;
use VanillaTests\Library\Vanilla\TestLogger;
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

        $this->testLogger = new TestLogger();
        $this->log = $this->container()->getArgs(LogDecorator::class, ['logger' => $this->testLogger]);
    }

    /**
     * Assert the last log context contains an expected context.
     *
     * @param array $context
     */
    protected function assertLastContext(array $context) {
        $actual = array_intersect_key($this->testLogger->last[2], $context);
        $this->assertSame($context, $actual);
    }

    /**
     * Test the basic decoration workflow.
     */
    public function testBasicDecoration() {
        $this->log->info('foo');
        $this->assertLastContext(['userid' => 0]);

        $context = $this->testLogger->getLastContext();
        $this->assertArrayHasKey('username', $context);
        $this->assertArrayHasKey('ip', $context);
        $this->assertArrayHasKey('timestamp', $context);
    }

    /**
     * Test the decorator's getter/setters.
     */
    public function testGetterSetter() {
        $this->log->setStaticContextDefaults(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $this->log->getStaticContextDefaults());
        $this->log->addStaticContextDefaults(['baz' => 'fra']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'fra'], $this->log->getStaticContextDefaults());

        $this->log->info('foo');
        $this->assertLastContext(['foo' => 'bar', 'baz' => 'fra']);
    }

    /**
     * A passed context should override the default context.
     */
    public function testOverride() {
        $this->log->info('foo', ['userid' => 123]);
        $this->assertSame(123, $this->testLogger->getLastContext()['userid']);
    }
}

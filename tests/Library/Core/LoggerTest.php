<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use VanillaTests\BootstrapTrait;
use VanillaTests\TestLogger;

/**
 * Tests for the `Logger` class.
 */
class LoggerTest extends TestCase {
    use BootstrapTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupBoostrapTrait();
    }

    /**
     * Test adding and removing a logger.
     */
    public function testAddRemoveLogger(): void {
        $logger = new TestLogger();
        \Logger::addLogger($logger);
        \Logger::event('foo', LogLevel::INFO, 'bar');
        $this->assertNotNull($logger->search([
            \Logger::FIELD_EVENT => 'foo',
            'message' => 'bar',
            'level' => LogLevel::INFO,
        ]));

        $logger->clear();
        \Logger::removeLogger($logger);
        \Logger::event('foo', LogLevel::INFO, 'bar');
        $this->assertNull($logger->search([
            \Logger::FIELD_EVENT => 'foo',
            'message' => 'bar',
            'level' => LogLevel::INFO,
        ]));
    }

    /**
     * You shouldn't be able to add a vanilla logger to the logger.
     */
    public function testAddInvalidLogger(): void {
        $logger = new \Vanilla\Logger();
        $this->expectException(\InvalidArgumentException::class);
        \Logger::addLogger($logger);
    }
}

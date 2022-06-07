<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Vanilla\Bootstrap;
use VanillaTests\BootstrapTrait;
use VanillaTests\VanillaTestCase;

/**
 * Some basic smoke tests for the `Vanilla\Bootstrap` class.
 */
final class BootstrapTest extends VanillaTestCase
{
    use BootstrapTrait;

    /**
     * Awknowledge we've called `Bootstrap::configureContainer` for code coverage.
     */
    public function testConfigureContainer(): void
    {
        $container = new Container();
        Bootstrap::configureContainer($container);

        $instances = $this->callOn($container, function () {
            return $this->instances;
        });
        $this->assertIsArray($instances);
        $this->assertEmpty($instances);
    }

    /**
     * The fast cache should work as a cache.
     */
    public function testFastCache(): void
    {
        /** @var CacheItemPoolInterface $cache */
        $cache = $this->container()->get(Bootstrap::CACHE_FAST);
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cache);

        $item = $cache->getItem(__FUNCTION__);
        $this->assertFalse($item->isHit());
        $item->set(__FUNCTION__);
        $this->assertSame(__FUNCTION__, $item->get());
    }
}

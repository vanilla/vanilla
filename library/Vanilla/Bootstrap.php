<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Container\Container;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Vanilla\Cache\CacheCacheAdapter;

/**
 * Contains static functions for bootstrapping Vanilla.
 *
 * This class is intended to be usable in the application and tests.
 */
class Bootstrap {
    public const CACHE_FAST = '@fast-cache';

    /**
     * Configure the application's dependency injection container.
     *
     * Note to developers: This is a relatively new method that does not have the entire bootstrap in it. It is intended
     * to use to refactor so that the app and tests use a similar config where differences are more easily spotted and
     * configures.
     *
     * THIS METHOD SHOULD NOT HAVE SIDE EFFECTS BEYOND CONTAINER CONFIG. DO NOT CREATE INSTANCES IN THIS METHOD.
     *
     * @param Container $container
     */
    public static function configureContainer(Container $container): void {
        $container
            ->rule(\Psr\Container\ContainerInterface::class)
            ->setAliasOf(\Garden\Container\Container::class)

            ->rule(\Interop\Container\ContainerInterface::class)
            ->setClass(InteropContainer::class)
            ->setShared(true)

            ->rule(InjectableInterface::class)
            ->addCall('setDependencies')

            ->rule(\DateTimeInterface::class)
            ->setAliasOf(\DateTimeImmutable::class)
            ->setConstructorArgs([null, null])
            ;

        // Caches
        $container
            ->rule(\Gdn_Cache::class)
            ->setShared(true)
            ->setFactory([\Gdn_Cache::class, 'initialize'])
            ->addAlias('Cache')

            ->rule(CacheInterface::class)
            ->setShared(true)
            ->setClass(CacheCacheAdapter::class)

            ->rule(CacheItemPoolInterface::class)
            ->setShared(true)
            ->setClass(Psr16Adapter::class)

            ->rule(self::CACHE_FAST)
            ->setShared(true)
            ->setFactory(function (ContainerInterface $container) {
                /** @var CacheCacheAdapter $mainCache */
                $mainCache = $container->get(CacheInterface::class);
                $mainCachePsr16 = new Psr16Adapter($mainCache);

                if (function_exists('apcu_fetch')) {
                    // @codeCoverageIgnoreStart
                    // This code doesn't usually get hit in unit tests, but was manually confirmed.
                    $cache = new ChainAdapter([
                        $mainCachePsr16,
                        new ApcuAdapter((string)$mainCache->getCache()->getPrefix(), 5)
                    ]);
                    return $cache;
                    // @codeCoverageIgnoreEnd
                }
                return $mainCachePsr16;
            });
        ;
    }
}

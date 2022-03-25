<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\MemcachedStore;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logging\ErrorLogger;

/**
 * Replacement for Symfony\Component\Cache\LockRegistry that uses a memcached based lock
 * instead of a file based lock.
 *
 * Notably the symfony file based lock in its lock registry:
 * - Doesn't work across a cluster (only on the app server level).
 * - Has exhibited deadlocking on various application servers.
 * https://higher-logic-llc.slack.com/archives/G010E9CKJ1H/p1646841643551049
 */
class ModelCacheLockRegistry {

    /** @var LockFactory|null */
    private $lockFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var string|null */
    private $currentlyLockedKey;

    /** @var int */
    private $ttl;

    /**
     * DI.
     *
     * @param \Gdn_Cache $cache
     * @param ConfigurationInterface $config
     */
    public function __construct(\Gdn_Cache $cache, ConfigurationInterface $config) {
        // flock or memcached.
        $lockStore = $config->get('Cache.LockStore', 'memcached');
        if ($cache instanceof \Gdn_Memcached && $lockStore === 'memcached') {
            $store = new MemcachedStore($cache->getMemcached());
        } else {
            $store = new FlockStore(PATH_CACHE . '/locks');
        }

        // We may want to play with this in the future.
        $this->ttl = $config->get('Cache.LockTTL', 15);
        $this->lockFactory = new LockFactory($store);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void {
        $this->logger = $logger;
    }

    /**
     * Create a lock instance.
     *
     * @param string $key
     * @param int|null $ttl Use a specific TTL instead of the default one.
     *
     * @return LockInterface
     */
    public function createLock(string $key, int $ttl = null): LockInterface {
        $ttl = $ttl ?? $this->ttl;
        $key = 'lock.'.$key;
        $lock = $this->lockFactory->createLock($key, $ttl, true);
        return $lock;
    }

    /**
     * Compute a cached value with a lock.
     * Method signature matches that of a callback to a symfony contract cache.
     *
     * @param callable $callback
     * @param ItemInterface $item
     * @param bool $save
     * @param CacheInterface $pool
     * @param \Closure|null $setMetadata
     *
     * @return mixed
     */
    public function compute(
        callable $callback,
        ItemInterface $item,
        bool &$save,
        CacheInterface $pool,
        \Closure $setMetadata = null
    ) {
        if ($this->currentlyLockedKey === $item->getKey()) {
            /* @codeCoverageIgnoreStart */
            // Somehow the callback called itself again. Don't deadlock on ourselves.
            // Just let it run itself.
            ErrorLogger::warning("ModelCache tried to generate a lock recursively", ["modelCache", "lock"], [
                'lockKey' => $item->getKey(),
            ]);
            return $callback($item, $save);
            /* @codeCoverageIgnoreEnd */
        }
        $lock = $this->createLock($item->getKey());
        $this->currentlyLockedKey = $item->getKey();
        try {
            while (true) {
                // race to get the lock in non-blocking mode
                $locked = $lock->acquire(false);
                if ($locked) {
                    $this->logger && $this->logger->info(
                        'Lock aquired, now computing item "{key}"',
                        ['key' => $item->getKey()]
                    );

                    $value = $callback($item, $save);

                    if ($save) {
                        if ($setMetadata) {
                            $setMetadata($item);
                        }

                        $pool->save($item->set($value));
                        $save = false;
                    }

                    return $value;
                }
                // if we failed the race, retry locking in blocking mode to wait for the winner
                if ($lock instanceof SharedLockInterface) {
                    $lock->acquireRead(true);
                } else {
                    $this->blockUntilLockReleased($lock);
                }

                // Because null is a perfectly valid value
                // We need a mechanism to determine if no value was found.
                // A generation callback that throws suits this purpose.
                static $signalingException, $signalingCallback;
                $signalingException = $signalingException ?? unserialize("O:9:\"Exception\":1:{s:16:\"\0Exception\0trace\";a:0:{}}");
                $signalingCallback = $signalingCallback ?? function () use ($signalingException) {
                    throw $signalingException;
                };

                try {
                    $value = $pool->get($item->getKey(), $signalingCallback, 0);
                    $this->logger && $this->logger->info('Item "{key}" retrieved after lock was released', ['key' => $item->getKey()]);
                    $save = false;

                    // We found a value in the cache.
                    return $value;
                } catch (\Exception $e) {
                    if ($signalingException !== $e) {
                        // A different exception occured during hydration. Throw it up.
                        throw $e;
                    }

                    // We caught our signalling exception.
                    // Log if we can then try again.
                    // This means between the lock was released but we didn't find a value in the cache afterwards.
                    $this->logger && $this->logger->info('Item "{key}" not found while lock was released, now retrying', ['key' => $item->getKey()]);
                }
            }
        } finally {
            $this->currentlyLockedKey = null;
            $lock->release();
        }
    }

    /**
     * Wait until a lock is released.
     *
     * @param LockInterface $lock
     */
    public function blockUntilLockReleased(LockInterface $lock) {
        while (true) {
            if (!$lock->isAcquired()) {
                return;
            } else {
                usleep((100 + random_int(-10, 10)) * 1000);
            }
        }
    }
}

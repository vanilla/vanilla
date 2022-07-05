<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Container\Container;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\MemcachedStore;
use Symfony\Component\Lock\Store\PdoStore;

/**
 * Service for acquiring Symfony\Component\Lock\LockInterface instances.
 * Similar to Symfony\Component\Lock\LockFactory.
 */
class LockService extends LockFactory
{
    /** @var Container */
    private $container;

    /** @var string */
    private $keyPrefix = "";

    /**
     * DI.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        parent::__construct($this->createMemcachedStore() ?? $this->createPdoStore());
    }

    /**
     * Overridden to apply a custom prefix.
     * @inheritDoc
     */
    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface
    {
        if ($this->keyPrefix) {
            $resource = $this->keyPrefix . "." . $resource;
        }
        return parent::createLock($resource, $ttl, $autoRelease);
    }

    /**
     * @return MemcachedStore|null
     */
    protected function createMemcachedStore(): ?MemcachedStore
    {
        $cache = $this->container->get(\Gdn_Cache::class);
        if (!$cache instanceof \Gdn_Memcached) {
            return null;
        }

        if (!$cache->activeEnabled()) {
            return null;
        }

        $memcached = $cache->getMemcached();
        $this->keyPrefix = $cache->getPrefix();
        return new MemcachedStore($memcached);
    }

    /**
     * @return PdoStore
     */
    protected function createPdoStore(): PdoStore
    {
        $db = $this->container->get(\Gdn_Database::class);
        $pdo = $db->connection();
        return new PdoStore($pdo, [
            "db_table" => "GDN_lock",
            "db_id_col" => "lockID",
            "db_token_col" => "uniquenessToken",
            "db_expiration_col" => "expiration",
        ]);
    }
}

<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Cache lock that can be stolen by a more recent operation.
 */
class StealableLock
{
    /** @var \Gdn_Cache */
    private $cache;

    /** @var string */
    private $lockKey;

    /**
     * DI.
     *
     * @param Gdn_Cache $cache
     * @param string $lockKey
     */
    public function __construct(\Gdn_Cache $cache, string $lockKey)
    {
        $this->cache = $cache;
        $this->lockKey = $lockKey;
    }

    /**
     * Refresh the cache lock. Will throw an error if the existing cache key doesn't match the passed uuid.
     *
     * @param string $existingUUID
     * @return void
     *
     * @throws LockStolenException
     */
    public function refresh(string $existingUUID): void
    {
        $existing = $this->cache->get($this->lockKey);
        if ($existing === null) {
            $wasSuccessful = $this->cache->add($this->lockKey, $existingUUID);
            if (!$wasSuccessful) {
                // Something else already inserted between our if and our add.
                throw new LockStolenException($this->lockKey);
            }
        } elseif ($existing !== $existingUUID) {
            throw new LockStolenException($this->lockKey);
        }
    }

    /**
     * Steal the lock.
     *
     * @return string
     * @throws Exception
     */
    public function steal(): string
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->cache->store($this->lockKey, $uuid);
        return $uuid;
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release(): bool
    {
        $released = $this->cache->remove($this->lockKey);
        if ($released) {
            return true;
        } else {
            return false;
        }
    }
}

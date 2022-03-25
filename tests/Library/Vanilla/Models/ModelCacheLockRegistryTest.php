<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Models\ModelCache;
use Vanilla\Models\ModelCacheLockRegistry;
use VanillaTests\BootstrapTestCase;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\MemcachedTestTrait;

/**
 * Tests for the model cache lock registry with memcached.
 */
class ModelCacheLockRegistryTest extends BootstrapTestCase {

    use MemcachedTestTrait;
    use ExpectExceptionTrait;

    /** @var ModelCacheLockRegistry */
    private $lockRegistry;

    /** @var ModelCache */
    private $modelCache;

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->lockRegistry = $this->container()->get(ModelCacheLockRegistry::class);
        $this->lockRegistry->setLogger($this->getTestLogger());
        $this->modelCache = new ModelCache('modelCache', \Gdn::cache());
        $this->modelCache->applyLockRegistry($this->lockRegistry);
        self::$memcached->flush();
    }

    /**
     * Make basic hydration works.
     */
    public function testWorks() {
        $result = $this->modelCache->getCachedOrHydrate(['arg1'], function () {
            return 'hydrated';
        });
        $this->assertEquals('hydrated', $result);
        $this->assertLogMessage("Lock aquired, now computing item");
    }

    /**
     * Test that things work properly even if a lock is already aquired and doesn't generate an item.
     */
    public function testOnAlreadyAquiredLock() {
        $key = $this->modelCache->createCacheKey(["arg1"], true);
        $lock = $this->lockRegistry->createLock($key, 2);
        $aquired = $lock->acquire();
        $this->assertTrue(true, $aquired);
        $result = $this->modelCache->getCachedOrHydrate(['arg1'], function () {
            return 'hydrated';
        });
        $this->assertEquals('hydrated', $result);
        $this->assertLogMessage("not found while lock was released, now retrying");
    }

    /**
     * Test exceptions being thrown at various phases of the generation.
     */
    public function testErrorDuringHydration() {
        // Exception bubbles up.
        $this->runWithExpectedExceptionMessage("wtf", function () {
            $this->modelCache->getCachedOrHydrate([], function () {
                throw new \Exception("wtf");
            });
        });

        // This time an exception occurs in the second codepath after the lock is released.
        $key = $this->modelCache->createCacheKey([], true);
        $lock = $this->lockRegistry->createLock($key, 2);
        $aquired = $lock->acquire();
        $this->assertTrue(true, $aquired);
        // Exception bubbles up.
        $this->runWithExpectedExceptionMessage("wtf", function () {
            $this->modelCache->getCachedOrHydrate([], function () {
                throw new \Exception("wtf");
            });
        });

        // We can still go generate the cached value.
        $result = $this->modelCache->getCachedOrHydrate([], function () {
            return 'hydrated';
        });
        $this->assertEquals('hydrated', $result);
    }
}

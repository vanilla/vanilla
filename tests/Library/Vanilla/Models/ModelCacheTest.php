<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\ModelCache;
use Vanilla\Models\PipelineModel;
use VanillaTests\Fixtures\TestCache;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `ModelCache` class.
 */
class ModelCacheTest extends SiteTestCase
{
    use SchedulerTestTrait;

    /**
     * @var PipelineModel
     */
    private $model;

    /**
     * @var ModelCache
     */
    private $modelCache;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (\Gdn_DatabaseStructure $st, \Gdn_SQLDriver $sql) {
            $st->table("model")
                ->primaryKey("modelID")
                ->column("name", "varchar(50)")
                ->set();
            $sql->truncate("model");
        });

        $this->model = $this->container()->getArgs(PipelineModel::class, ["model"]);
        $this->modelCache = new ModelCache($this->model->getTable(), new TestCache());
    }

    /**
     * Test that we can get and invalidate from the cache.
     */
    public function testGetInvalidate()
    {
        $this->insertOne("foo");
        $this->insertOne("bar");

        $hydrator = function () {
            return $this->model->select();
        };

        $initialResult = $this->modelCache->getCachedOrHydrate([], $hydrator);

        // Insert another. We have no automatic cache invalidation enabled.
        $this->insertOne("bar2");

        $secondResult = $this->modelCache->getCachedOrHydrate([], $hydrator);

        $this->assertSame($initialResult, $secondResult);
        $this->assertSame(["foo", "bar"], array_column($secondResult, "name"));

        // Invalidate and see they are different.
        $this->modelCache->invalidateAll();

        $finalResult = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(["foo", "bar", "bar2"], array_column($finalResult, "name"));
    }

    /**
     * Test that we can get and invalidate from the cache.
     */
    public function testAutoInvalidation()
    {
        $this->model->addPipelineProcessor($this->modelCache->createInvalidationProcessor());
        $fooID = $this->insertOne("foo");
        $this->insertOne("bar");

        $hydrator = function () {
            return $this->model->select();
        };

        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(["foo", "bar"], array_column($result, "name"));

        // Insert another. This should invalidate the cache.
        $bar2ID = $this->insertOne("bar2");
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(["foo", "bar", "bar2"], array_column($result, "name"));

        // Delete an item.
        $this->model->delete(["modelID" => $bar2ID]);
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(["foo", "bar"], array_column($result, "name"));

        // Update an item.
        $this->model->update(["name" => "foo update"], ["modelID" => $fooID]);
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(["foo update", "bar"], array_column($result, "name"));
    }

    /**
     * Test the full record cache model.
     */
    public function testFullRecordCacheModelInvalidation()
    {
        /** @var FullRecordCacheModel $model */
        $model = $this->container()->getArgs(FullRecordCacheModel::class, ["model", new TestCache()]);
        $this->model = $model;

        $fooID = $this->insertOne("foo");
        $this->insertOne("bar");

        $result = $this->model->select();
        $this->assertSame(["foo", "bar"], array_column($result, "name"));

        // Insert another. This should invalidate the cache.
        $bar2ID = $this->insertOne("bar2");
        $result = $this->model->getAll();
        $this->assertSame(["foo", "bar", "bar2"], array_column($result, "name"));

        // Delete an item.
        $this->model->delete(["modelID" => $bar2ID]);
        $result = $this->model->getAll();
        $this->assertSame(["foo", "bar"], array_column($result, "name"));

        // Update an item.
        $this->model->update(["name" => "foo update"], ["modelID" => $fooID]);
        $result = $this->model->getAll();
        $this->assertSame(["foo update", "bar"], array_column($result, "name"));

        // Make sure where's still work.
        $result = $this->model->select(["name" => "bar"]);
        $this->assertSame(["bar"], array_column($result, "name"));

        $result = $this->model->selectSingle(["modelID" => $fooID]);
        $this->assertSame("foo update", $result["name"]);
    }

    /**
     * We have a feature flag to disable the new caching if it causes issues.
     */
    public function testCacheDisabled()
    {
        FeatureFlagHelper::clearCache();
        $this->runWithConfig(
            [
                "Feature." . ModelCache::DISABLE_FEATURE_FLAG . ".Enabled" => true,
            ],
            function () {
                $mockCache = $this->createMock(TestCache::class);
                $mockCache->expects($this->never())->method($this->anything());
                $this->modelCache = new ModelCache($this->model->getTable(), $mockCache);
                $this->testAutoInvalidation();
            }
        );
        FeatureFlagHelper::clearCache();
    }

    /**
     * Insert a basic test row.
     *
     * @param string $name
     * @return int
     */
    private function insertOne(string $name = "foo"): int
    {
        $id = $this->model->insert(["name" => $name]);
        return $id;
    }

    /**
     * Test that our cache hydration can be deferred into the scheduler.
     */
    public function testDeferredCacheHydration()
    {
        $this->getScheduler()->pause();
        $hydrateCount = 0;

        $getDeferred = function () use (&$hydrateCount) {
            return $this->modelCache->getCachedOrHydrate(
                ["myKey"],
                function () use (&$hydrateCount) {
                    $hydrateCount++;
                    return "hydrated";
                },
                [
                    ModelCache::OPT_DEFAULT => "default",
                    ModelCache::OPT_SCHEDULER => $this->getScheduler(),
                ]
            );
        };

        $result = $getDeferred();
        $this->assertEquals("default", $result, "The default value is set.");
        $getDeferred();
        $getDeferred();
        $getDeferred();
        $this->assertEquals(0, $hydrateCount, "Hydrate should not have been called yet.");

        // Execute the generation of the cache value.
        $this->getScheduler()->resume();
        $result = $getDeferred();
        $this->assertEquals("hydrated", $result);
        $getDeferred();
        $this->assertEquals(1, $hydrateCount);
    }

    /**
     * Test that a cache key is deleted if scheduled hydration is run after reschedule threshold time limit is exceeded.
     */
    public function testDeleteCacheKeyAfterThreshold()
    {
        $cache = new TestCache();
        $modelCache = new ModelCache("test-timeout", $cache);
        $scheduler = $this->getScheduler();

        $countHydrated = 0;

        $getDeferred = function () use ($modelCache, $scheduler, &$countHydrated) {
            return $modelCache->getCachedOrHydrate(
                ["myKey"],
                function () use (&$countHydrated) {
                    $countHydrated++;
                    return "hydrated";
                },
                [
                    ModelCache::OPT_DEFAULT => "default",
                    ModelCache::OPT_SCHEDULER => $scheduler,
                ]
            );
        };

        $this->getScheduler()->pause();
        $time = CurrentTimeStamp::mockTime("1980-06-17");
        $result = $getDeferred();
        $this->assertEquals("default", $result);
        $this->assertEquals(0, $countHydrated);

        // More than 30 seconds pass and we do not hydrate and clear that cache key.
        $time = CurrentTimeStamp::mockTime($time->modify("+31 seconds"));
        $this->getScheduler()->resume();
        $this->assertEquals(0, $countHydrated);

        // Right at the threshold, it will hydrate this time.
        $this->getScheduler()->pause();
        $result = $getDeferred();
        $this->assertEquals("default", $result);
        $this->assertEquals(0, $countHydrated);

        // Move the time past the threshold.
        CurrentTimeStamp::mockTime($time->modify("+30 seconds"));
        $this->getScheduler()->resume();
        $result = $getDeferred();
        $this->assertEquals("hydrated", $result);
        $this->assertEquals(1, $countHydrated);
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;
use Vanilla\Models\PipelineModel;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `Model` class.
 */
class ModelCacheTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var PipelineModel
     */
    private $model;

    /**
     * @var ModelCache
     */
    private $modelCache;

    /**
     * Install the site and set up a test table.
     */
    public static function setupBeforeClass(): void {
        static::setupBeforeClassSiteTestTrait();

        static::container()->call(function (
            \Gdn_DatabaseStructure $st
        ) {
            $st->table('model')
                ->primaryKey('modelID')
                ->column('name', 'varchar(50)')
                ->set()
            ;
        });
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->container()->call(function (
            \Gdn_SQLDriver $sql
        ) {
            $sql->truncate('model');
        });

        $this->model = $this->container()->getArgs(PipelineModel::class, ['model']);
        $this->modelCache = ModelCache::fromModel($this->model);

        // Apply a specific cache.
        $this->modelCache->setCache(new \Gdn_Dirtycache());
    }

    /**
     * Test that we can get and invalidate from the cache.
     */
    public function testGetInvalidate() {
        $this->insertOne('foo');
        $this->insertOne('bar');

        $hydrator = function () {
            return $this->model->get();
        };

        $initialResult = $this->modelCache->getCachedOrHydrate([], $hydrator);

        // Insert another. We have no automatic cache invalidation enabled.
        $this->insertOne('bar2');

        $secondResult = $this->modelCache->getCachedOrHydrate([], $hydrator);

        $this->assertSame($initialResult, $secondResult);
        $this->assertSame(['foo', 'bar'], array_column($secondResult, 'name'));

        // Invalidate and see they are different.
        $this->modelCache->invalidateAll();

        $finalResult = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(['foo', 'bar', 'bar2'], array_column($finalResult, 'name'));
    }

    /**
     * Test that we can get and invalidate from the cache.
     */
    public function testAutoInvalidation() {
        $this->model->addPipelinePostProcessor($this->modelCache->createInvalidationProcessor());
        $fooID = $this->insertOne('foo');
        $this->insertOne('bar');

        $hydrator = function () {
            return $this->model->get();
        };

        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(['foo', 'bar'], array_column($result, 'name'));

        // Insert another. This should invalidate the cache.
        $bar2ID = $this->insertOne('bar2');
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(['foo', 'bar', 'bar2'], array_column($result, 'name'));

        // Delete an item.
        $this->model->delete(['modelID' => $bar2ID]);
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(['foo', 'bar'], array_column($result, 'name'));

        // Update an item.
        $this->model->update(['name' => 'foo update'], ['modelID' => $fooID]);
        $result = $this->modelCache->getCachedOrHydrate([], $hydrator);
        $this->assertSame(['foo update', 'bar'], array_column($result, 'name'));
    }

    /**
     * Test the full record cache model.
     */
    public function testFullRecordCacheModelInvalidation() {
        /** @var FullRecordCacheModel $model */
        $model = $this->container()->getArgs(FullRecordCacheModel::class, ['model']);
        $model->getModelCache()->setCache(new \Gdn_Dirtycache());
        $this->model = $model;

        $fooID = $this->insertOne('foo');
        $this->insertOne('bar');

        $result = $this->model->get();
        $this->assertSame(['foo', 'bar'], array_column($result, 'name'));

        // Insert another. This should invalidate the cache.
        $bar2ID = $this->insertOne('bar2');
        $result = $this->model->getAll();
        $this->assertSame(['foo', 'bar', 'bar2'], array_column($result, 'name'));

        // Delete an item.
        $this->model->delete(['modelID' => $bar2ID]);
        $result = $this->model->getAll();
        $this->assertSame(['foo', 'bar'], array_column($result, 'name'));

        // Update an item.
        $this->model->update(['name' => 'foo update'], ['modelID' => $fooID]);
        $result = $this->model->getAll();
        $this->assertSame(['foo update', 'bar'], array_column($result, 'name'));

        // Make sure where's still work.
        $result = $this->model->get(['name' => 'bar']);
        $this->assertSame(['bar'], array_column($result, 'name'));

        $result = $this->model->selectSingle(['modelID' => $fooID]);
        $this->assertSame('foo update', $result['name']);
    }

    /**
     * Insert a basic test row.
     *
     * @param string $name
     * @return int
     */
    private function insertOne(string $name = 'foo'): int {
        $id = $this->model->insert(['name' => $name]);
        return $id;
    }
}

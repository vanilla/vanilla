<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use Garden\Events\ResourceEvent;
use Garden\Events\ResourceEventLimitException;
use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Models\PipelineModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Database\ProcessorFixture;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `PruneProcessor` class.
 */
class ResourceEventProcessorTest extends TestCase {

    use SiteTestTrait;
    use SetupTraitsTrait;
    use EventSpyTestTrait;

    /**
     * @var PipelineModel
     */
    private $model;

    /**
     * @var Operation\ResourceEventProcessor
     */
    private $eventProcessor;

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
                ->set();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();

        $this->container()->call(function (
            \Gdn_SQLDriver $sql
        ) {
            $sql->truncate('model');
        });

        $this->model = $this->container()->getArgs(PipelineModel::class, ['model']);
        $this->eventProcessor = $this->container()->get(Operation\ResourceEventProcessor::class);
        $this->model->addPipelineProcessor($this->eventProcessor);
    }

    /**
     * Test that delets events are gathered and fired.
     */
    public function testInsert() {
        $this->model->insert(['name' => 'item1']);
        $this->assertEventsDispatched([
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item1', 'modelID' => 1]),
        ]);
    }

    /**
     * Test that delets events are gathered and fired.
     */
    public function testUpdateSingle() {
        $this->model->insert(['name' => 'item1']);
        $this->model->update(['name' => 'item2'], ['modelID' => 1]);
        $this->assertEventsDispatched([
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item1', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_UPDATE, ['name' => 'item2', 'modelID' => 1]),
        ]);
    }

    /**
     * Test that delets events are gathered and fired.
     */
    public function testUpdateMultiple() {
        $this->model->insert(['name' => 'item1']);
        $this->model->insert(['name' => 'item2']);
        $this->model->update(['name' => 'name reset'], ['modelID <' => 3]);
        $this->assertEventsDispatched([
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item1', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item2', 'modelID' => 2]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_UPDATE, ['name' => 'name reset', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_UPDATE, ['name' => 'name reset', 'modelID' => 2]),
        ]);
    }

    /**
     * Test that delets events are gathered and fired.
     */
    public function testDeleteMultiple() {
        $this->model->insert(['name' => 'item1']);
        $this->model->insert(['name' => 'item2']);
        $this->model->delete(['modelID <' => 3]);
        $this->assertEventsDispatched([
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item1', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item2', 'modelID' => 2]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_DELETE, ['name' => 'item1', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_DELETE, ['name' => 'item2', 'modelID' => 2]),
        ]);
    }

    /**
     * Test that if an operation fails, no events will be dispatched from it.
     */
    public function testFailureNoDispatch() {
        $this->model->insert(['name' => 'item1']);
        $this->model->insert(['name' => 'item2']);

        $processorFixtures = new ProcessorFixture(function () {
            throw new \Exception('ERROR!');
        });

        $this->model->addPipelineProcessor($processorFixtures);

        $exception = null;
        try {
            $this->model->delete(['modelID <' => 3]);
        } catch (\Exception $e) {
            $exception = $e;
            // Do nothing.
        }

        $this->assertInstanceOf(\Exception::class, $exception);

        // An exception was thrown after the delete events were processed, but the events were not dispatched.
        $this->assertEventsDispatched([
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item1', 'modelID' => 1]),
            $this->expectedResourceEvent('model', ResourceEvent::ACTION_INSERT, ['name' => 'item2', 'modelID' => 2]),
        ]);
    }

    /**
     * Test the limit of resources that can be processed.
     */
    public function testLimit() {
        $this->eventProcessor->setUpdateLimit(2);
        $this->model->insert(['name' => 'item1']);
        $this->model->insert(['name' => 'item2']);
        $this->model->insert(['name' => 'item2']);

        // Only affects 2 rows. No limit needed.
        $this->model->update(['name' => 'name reset'], ['modelID <' => 3]);

        $this->expectException(ResourceEventLimitException::class);
        $this->model->update(['name' => 'name reset'], ['modelID <' => 4]);
    }
}

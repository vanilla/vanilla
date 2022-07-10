<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Database\Operation;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;
use VanillaTests\Fixtures\Database\ProcessorFixture;

/**
 * Tests for the PipelineModel class.
 */
class PipelineModelTest extends ModelTest {

    /**
     * @var PipelineModel
     */
    protected $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        /** @var PipelineModel $model */
        $model = $this->container()->getArgs(PipelineModel::class, ['model']);
        $model->addPipelineProcessor(new JsonFieldProcessor(['attributes']));
        $this->model = $model;
    }

    /**
     * Verify primary action persists when making additional calls to the pipeline within adjacent processors.
     */
    public function testPersistentAction(): void {
        $model = $this->container()->getArgs(PipelineModel::class, ["model"]);
        $rowID = $model->insert(["name" => __FUNCTION__]);
        $processor = new ProcessorFixture(function (Operation $operation) {
            if ($operation->getType() !== Operation::TYPE_DELETE) {
                $operation->getCaller()->delete(["modelID" => 999]);
            }
        });
        $model->addPipelineProcessor($processor);
        $result = $model->get(["modelID" => $rowID]);
        $this->assertSame([[
            "modelID" => $rowID,
            "name" => __FUNCTION__,
        ]], $result);
    }
}
